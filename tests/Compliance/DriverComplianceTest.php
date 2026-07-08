<?php

namespace Elqora\Dgp\Tests\Compliance;

use PHPUnit\Framework\TestCase;
use Elqora\Dgp\Tests\Fixtures\Handlers\SmmTestHandler;
use Elqora\Dgp\Tests\Fixtures\Handlers\ManualTestHandler;
use Elqora\Dgp\Manifest\Capability;
use Elqora\Dgp\Catalog\Schemas\Contracts\ServiceSchemaCatalogContract;
use Elqora\Dgp\Ui\Contracts\UiManifestContract;
use Elqora\Dgp\Events\Contracts\WebhookContract;
use Elqora\Dgp\Assets\Contracts\PrivateAssetContract;
use Elqora\Dgp\Runtime\Plan;
use Elqora\Dgp\Runtime\StartResult;
use Elqora\Dgp\Support\Hydrator;
use Elqora\Dgp\Deliveries\InitializationDelivery;
use Elqora\Dgp\Deliveries\FulfillmentDelivery;
use Elqora\Dgp\Deliveries\DeliveryStatus;
use Elqora\Dgp\Snapshots\OrderSnapshot;
use Elqora\Dgp\Catalog\Schemas\ServiceProps;
use Elqora\Dgp\Catalog\Schemas\ServicePropsValidator;
use Elqora\Dgp\Runtime\InitializeRequest;
use Elqora\Dgp\Runtime\StartRequest;
use Elqora\Dgp\Runtime\RuntimeContext;
use Elqora\ConfigKit\Contracts\ProvidesConfigSchema;
use Elqora\ConfigKit\Schema\ConfigSchema;
use Elqora\ConfigKit\Schema\UiConfigSchema;
use Elqora\ConfigKit\Support\ConfigBag;
use Elqora\ConfigKit\Support\ConfigValidationResult;
use InvalidArgumentException;

class DriverComplianceTest extends TestCase
{
    public function testSmmHandlerCapabilityMatching(): void
    {
        $handler = new SmmTestHandler();
        $manifest = $handler->manifest()->value();

        $capabilities = $manifest->capabilities;

        // Check optional contract vs capability rules
        if (in_array(Capability::SERVICE_SCHEMA_CATALOG, $capabilities, true)) {
            $this->assertInstanceOf(ServiceSchemaCatalogContract::class, $handler);
        } else {
            $this->assertNotInstanceOf(ServiceSchemaCatalogContract::class, $handler);
        }

        if (in_array(Capability::UI_CONTRIBUTIONS, $capabilities, true)) {
            $this->assertInstanceOf(UiManifestContract::class, $handler);
        } else {
            $this->assertNotInstanceOf(UiManifestContract::class, $handler);
        }
    }

    public function testManualHandlerCapabilityMatching(): void
    {
        $handler = new ManualTestHandler();
        $manifest = $handler->manifest()->value();

        $capabilities = $manifest->capabilities;

        $this->assertContains(Capability::SERVICE_SCHEMA_CATALOG, $capabilities);
        $this->assertInstanceOf(ServiceSchemaCatalogContract::class, $handler);
    }

    public function testPlanRoundTripAndStability(): void
    {
        $plan = new Plan(
            id: null,
            key: 'plan-123',
            state: ['reserved' => true]
        );

        $serialized = Hydrator::serialize($plan);
        $this->assertNull($serialized['id']);
        $this->assertEquals('plan-123', $serialized['key']);

        $hydrated = Hydrator::hydrate(Plan::class, $serialized);
        $this->assertTrue(Hydrator::compare($plan, $hydrated));

        // Hydrated with a persisted ID
        $serialized['id'] = 456;
        $hydratedWithId = Hydrator::hydrate(Plan::class, $serialized);
        $this->assertEquals(456, $hydratedWithId->id);
    }

    public function testStartResultRoundTripAndStability(): void
    {
        $startResult = new StartResult(
            id: null,
            key: 'start-123',
            state: ['running' => true]
        );

        $serialized = Hydrator::serialize($startResult);
        $this->assertNull($serialized['id']);
        $this->assertEquals('start-123', $serialized['key']);

        $hydrated = Hydrator::hydrate(StartResult::class, $serialized);
        $this->assertTrue(Hydrator::compare($startResult, $hydrated));
    }

    public function testDeliveryParentValidation(): void
    {
        // Initialization delivery allows planId but rejects startId
        $init = new InitializationDelivery(
            id: null,
            key: 'init-1',
            status: DeliveryStatus::PENDING,
            label: 'Review Contract',
            planId: 'plan-abc'
        );
        $this->assertEquals('plan-abc', $init->planId);
        $this->assertNull($init->startId);

        $this->expectException(InvalidArgumentException::class);
        new InitializationDelivery(
            id: null,
            key: 'init-1',
            status: DeliveryStatus::PENDING,
            label: 'Review Contract',
            planId: 'plan-abc',
            startId: 'start-xyz'
        );
    }

    public function testFulfillmentDeliveryParentValidation(): void
    {
        // Fulfillment delivery allows startId but rejects planId
        $fulfillment = new FulfillmentDelivery(
            id: null,
            key: 'full-1',
            status: DeliveryStatus::PROCESSING,
            label: 'Provide License Key',
            startId: 'start-abc'
        );
        $this->assertEquals('start-abc', $fulfillment->startId);
        $this->assertNull($fulfillment->planId);

        $this->expectException(InvalidArgumentException::class);
        new FulfillmentDelivery(
            id: null,
            key: 'full-1',
            status: DeliveryStatus::PROCESSING,
            label: 'Provide License Key',
            planId: 'plan-abc',
            startId: 'start-xyz'
        );
    }

    public function testOrderSnapshotRoundTripFixture(): void
    {
        $snapshotJson = '{
            "version": "1",
            "mode": "prod",
            "builtAt": "2026-07-07T10:30:00.000Z",
            "selection": {
                "tag": "tag:instagram-likes",
                "buttons": ["field:premium", "option:quality-ultra"],
                "fields": [
                    {"id": "field:premium", "type": "toggle"},
                    {"id": "field:quality", "type": "select", "selectedOptions": ["option:quality-ultra"]},
                    {"id": "field:quantity", "type": "number"}
                ]
            },
            "inputs": {
                "form": {
                    "quality": "option:quality-ultra",
                    "quantity": 1000
                },
                "selections": {
                    "field:quality": ["option:quality-ultra"]
                }
            },
            "quantity": 1000,
            "quantitySource": {
                "kind": "field",
                "id": "field:quantity",
                "rule": {
                    "valueBy": "value",
                    "clamp": {"min": 100, "max": 10000}
                }
            },
            "min": 100,
            "max": 10000,
            "services": [101, 103],
            "serviceMap": {
                "tag:instagram-likes": [101],
                "option:quality-ultra": [103]
            },
            "fallbacks": {
                "global": {
                    "103": [106]
                }
            },
            "utilities": [],
            "meta": {
                "schema_version": "1",
                "workspaceId": "workspace:instagram",
                "builder": {
                    "commit": "commit:abc123"
                }
            }
        }';

        $payload = json_decode($snapshotJson, true);
        $snapshot = OrderSnapshot::fromArray($payload);

        $this->assertEquals('1', $snapshot->version());
        $this->assertEquals('prod', $snapshot->mode());
        $this->assertEquals('tag:instagram-likes', $snapshot->tag());
        $this->assertEquals(1000, $snapshot->quantity());
        $this->assertEquals('field', $snapshot->quantitySource()->kind);
        $this->assertEquals(100, $snapshot->min());
        $this->assertEquals(10000, $snapshot->max());
        $this->assertContains(101, $snapshot->services());
        $this->assertEquals([103], $snapshot->servicesForNode('option:quality-ultra'));

        $serialized = Hydrator::serialize($snapshot);
        $this->assertEquals($payload['builtAt'], $serialized['builtAt']);
        $this->assertEquals($payload['quantitySource']['kind'], $serialized['quantitySource']['kind']);
    }

    public function testOrderSnapshotFallbackLookup(): void
    {
        $payload = [
            'version' => '1',
            'mode' => 'test',
            'builtAt' => '2026-07-07T10:30:00.000Z',
            'selection' => ['tag' => 'tag:instagram-likes', 'buttons' => [], 'fields' => []],
            'inputs' => ['form' => [], 'selections' => []],
            'quantity' => 100,
            'quantitySource' => ['kind' => 'fixed'],
            'min' => 100,
            'max' => 10000,
            'services' => [101, 103],
            'serviceMap' => [],
            'utilities' => [],
            'fallbacks' => [
                'global' => [
                    103 => [106, '108'],
                    'tag:instagram-likes' => ['tag:inst-fallback'],
                ],
                'nodes' => [
                    103 => [
                        'option:ultra' => [104, 106],
                    ]
                ]
            ]
        ];

        $snapshot = OrderSnapshot::fromArray($payload);

        // 1. Global-only lookup
        $this->assertEquals([106, 108], $snapshot->fallbacksFor(103));
        $this->assertEquals(['tag:inst-fallback'], $snapshot->fallbacksFor('tag:instagram-likes'));

        // 2. Node-context lookup (deduplicated, preserving node precedence: node [104, 106] + global [106, 108] => [104, 106, 108])
        $this->assertEquals([104, 106, 108], $snapshot->fallbacksFor(103, 'option:ultra'));

        // 3. Unknown service ID returns empty list
        $this->assertEquals([], $snapshot->fallbacksFor(999));

        // 4. Unknown node ID fallback returns only global fallbacks
        $this->assertEquals([106, 108], $snapshot->fallbacksFor(103, 'option:unknown'));

        // 5. String/integer key coercion lookup
        $this->assertEquals([106, 108], $snapshot->fallbacksFor('103'));
    }

    public function testServicePropsValidationInvariant(): void
    {
        // Valid ServiceProps structure
        $validProps = [
            'filters' => [
                ['id' => 'tag:instagram', 'label' => 'Instagram']
            ],
            'fields' => [
                ['id' => 'field:premium', 'type' => 'toggle', 'label' => 'Premium']
            ],
            'option_effects_for_buttons' => [
                'field:premium' => [
                    'field:premium' => ['forceVisible' => true]
                ]
            ]
        ];

        $errors = ServicePropsValidator::validate($validProps);
        $this->assertEmpty($errors);

        // Invalid ServiceProps (missing trigger ID or target ID in node lists)
        $invalidProps = [
            'filters' => [
                ['id' => 'tag:instagram', 'label' => 'Instagram']
            ],
            'fields' => [
                ['id' => 'field:premium', 'type' => 'toggle', 'label' => 'Premium']
            ],
            'option_effects_for_buttons' => [
                'field:missing-trigger' => [
                    'field:premium' => ['forceVisible' => true]
                ]
            ]
        ];

        $errors = ServicePropsValidator::validate($invalidProps);
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('option_effects_for_buttons.field:missing-trigger', $errors);
    }

    public function testRuntimeContextPreservesContextAndMeta(): void
    {
        $context = new RuntimeContext(
            context: ['requestedBy' => 'customer'],
            meta: ['traceId' => 'trace-abc']
        );
        $this->assertEquals(['requestedBy' => 'customer'], $context->context);
        $this->assertEquals(['traceId' => 'trace-abc'], $context->meta);
    }

    public function testInitializeRequestRoundTripAndContext(): void
    {
        $snapshotJson = '{
            "version": "1",
            "mode": "test",
            "builtAt": "2026-07-07T10:30:00.000Z",
            "selection": {
                "tag": "tag:instagram-likes",
                "buttons": [],
                "fields": []
            },
            "inputs": {
                "form": {},
                "selections": {}
            },
            "quantity": 100,
            "quantitySource": {
                "kind": "fixed"
            },
            "min": 100,
            "max": 10000,
            "services": [],
            "serviceMap": {},
            "utilities": []
        }';
        $snapshot = OrderSnapshot::fromArray(json_decode($snapshotJson, true));

        $request = new InitializeRequest(
            orderId: 123,
            snapshot: $snapshot,
            runtimeContext: new RuntimeContext(
                context: ['accountId' => 48],
                meta: ['source' => 'checkout']
            )
        );

        $serialized = Hydrator::serialize($request);
        $this->assertEquals(123, $serialized['orderId']);
        $this->assertEquals(['accountId' => 48], $serialized['runtimeContext']['context']);

        $hydrated = Hydrator::hydrate(InitializeRequest::class, $serialized);
        $this->assertEquals(123, $hydrated->orderId);
        $this->assertNotNull($hydrated->runtimeContext);
        $this->assertEquals(['accountId' => 48], $hydrated->runtimeContext->context);
    }

    public function testStartRequestRoundTrip(): void
    {
        $request = new StartRequest(
            planId: 'plan-123',
            runtimeContext: new RuntimeContext(
                context: ['foo' => 'bar']
            )
        );

        $serialized = Hydrator::serialize($request);
        $this->assertEquals('plan-123', $serialized['planId']);

        $hydrated = Hydrator::hydrate(StartRequest::class, $serialized);
        $this->assertEquals('plan-123', $hydrated->planId);
        $this->assertNotNull($hydrated->runtimeContext);
        $this->assertEquals(['foo' => 'bar'], $hydrated->runtimeContext->context);
    }

    public function testDriverContractProvidesConfigSchema(): void
    {
        $manual = new ManualTestHandler();
        $smm = new SmmTestHandler();

        $this->assertInstanceOf(ProvidesConfigSchema::class, $manual);
        $this->assertInstanceOf(ProvidesConfigSchema::class, $smm);
    }

    public function testSmmHandlerProvidesSchemaAndValidation(): void
    {
        $handler = new SmmTestHandler();

        $schema = $handler->configSchema();
        $this->assertInstanceOf(ConfigSchema::class, $schema);

        $uiSchema = $handler->uiConfigSchema();
        $this->assertInstanceOf(UiConfigSchema::class, $uiSchema);

        // Safe null handling
        $nullVal = $handler->validateConfig(null);
        $this->assertFalse($nullVal->isOk());

        // Incomplete config (missing base_url and api_key)
        $bag = new ConfigBag(false, [], []);
        $val1 = $handler->validateConfig($bag);
        $this->assertFalse($val1->isOk());
        $this->assertArrayHasKey('base_url', $val1->errors());
        $this->assertArrayHasKey('api_key', $val1->errors());

        // Complete config (valid options & secrets)
        $bagValid = new ConfigBag(
            sandbox: false,
            options: ['base_url' => 'https://api.smm.example'],
            secrets: ['api_key' => 'secret-smm-key']
        );
        $val2 = $handler->validateConfig($bagValid);
        $this->assertTrue($val2->isOk());
    }

    public function testConfigBagOptionsSecretsSandboxFiltering(): void
    {
        $handler = new SmmTestHandler();
        $schema = $handler->configSchema();
        $this->assertNotNull($schema);

        // 1. Live mode ConfigBag
        $bagLive = new ConfigBag(
            sandbox: false,
            options: [
                'base_url' => 'https://live.smm.example',
                'timeout' => 45,
            ],
            secrets: [
                'api_key' => 'secret-smm-key',
            ]
        );

        $filteredLive = $bagLive->filterBySchema($schema);
        $this->assertFalse($filteredLive->isSandbox());
        $this->assertEquals('https://live.smm.example', $filteredLive->option('base_url'));
        $this->assertEquals(45, $filteredLive->option('timeout'));
        $this->assertEquals('secret-smm-key', $filteredLive->secret('api_key'));
        $this->assertNull($filteredLive->option('sandbox_user'));

        // 2. Sandbox mode ConfigBag
        $bagSandbox = new ConfigBag(
            sandbox: true,
            options: [
                'sandbox_user' => 'test-user',
            ]
        );

        $filteredSandbox = $bagSandbox->filterBySchema($schema);
        $this->assertTrue($filteredSandbox->isSandbox());
        $this->assertEquals('test-user', $filteredSandbox->option('sandbox_user'));
        $this->assertNull($filteredSandbox->option('base_url'));

        // Serialize output excludes secrets
        $serialized = $filteredLive->jsonSerialize();
        $this->assertArrayHasKey('options', $serialized);
        $this->assertArrayNotHasKey('secrets', $serialized);
    }

    public function testPublicConfigProjections(): void
    {
        $handler = new SmmTestHandler();
        $bag = new ConfigBag(
            sandbox: false,
            options: ['base_url' => 'https://live.smm.example'],
            secrets: ['api_key' => 'secret-smm-key']
        );

        $public = $handler->publicConfig($bag);
        $this->assertArrayHasKey('options', $public);
        $this->assertEquals('https://live.smm.example', $public['options']['base_url']);
        $this->assertArrayNotHasKey('secrets', $public);

        // Safe null handling
        $this->assertEquals([], $handler->publicConfig(null));
    }

    public function testRedactionBehavior(): void
    {
        $handler = new SmmTestHandler();

        $payload = [
            'base_url' => 'https://api.smm.example',
            'api_key' => 'secret-api-key-123',
            'auth' => [
                'token' => 'nested-secret-token',
                'user' => 'admin',
            ]
        ];

        $redacted = $handler->redactForLogs($payload);
        $this->assertEquals('https://api.smm.example', $redacted['base_url']);
        $this->assertEquals('[REDACTED]', $redacted['api_key']);
        $this->assertEquals('[REDACTED]', $redacted['auth']['token']);
        $this->assertEquals('admin', $redacted['auth']['user']);

        // Non-array returns unmodified
        $this->assertEquals('scalar-value', $handler->redactForLogs('scalar-value'));
    }
}
