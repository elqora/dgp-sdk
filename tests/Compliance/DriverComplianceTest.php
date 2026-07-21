<?php

namespace Elqora\Dgp\Tests\Compliance;

use PHPUnit\Framework\TestCase;
use Elqora\Chart\Charts\Chart;
use Elqora\Chart\Data\TabularData;
use Elqora\Chart\Enums\ChartType;
use Elqora\Chart\Enums\ValueType;
use Elqora\Chart\Series\Series;
use Elqora\Dgp\Catalog\Services\HandlerService;
use Elqora\Dgp\Catalog\Services\ServiceCapability;
use Elqora\Dgp\Catalog\Services\ServiceCapabilitySet;
use Elqora\Dgp\Catalog\Services\ServiceMeta;
use Elqora\Dgp\Insights\Analysis;
use Elqora\Dgp\Insights\Leaderboard;
use Elqora\Dgp\Insights\LeaderboardEntry;
use Elqora\Dgp\Insights\Scoreboard;
use Elqora\Dgp\Insights\ScoreboardItem;
use Elqora\Dgp\Manifest\AnalysisDefinition;
use Elqora\Dgp\Tests\Fixtures\Handlers\SmmTestHandler;
use Elqora\Dgp\Tests\Fixtures\Handlers\ManualTestHandler;
use Elqora\Dgp\Tests\Fixtures\Handlers\PaymentNotificationTestHandler;
use Elqora\Dgp\Manifest\Capability;
use Elqora\Dgp\Manifest\HandlerManifest;
use Elqora\Dgp\Manifest\ScoreboardItemDefinition;
use Elqora\Dgp\Catalog\Schemas\Contracts\ServiceSchemaCatalogContract;
use Elqora\Dgp\Ui\Contracts\UiManifestContract;
use Elqora\Dgp\Events\Contracts\WebhookContract;
use Elqora\Dgp\Assets\Contracts\PrivateAssetContract;
use Elqora\Dgp\Runtime\Plan;
use Elqora\Dgp\Runtime\PrepareRequest;
use Elqora\Dgp\Runtime\PreparationResult;
use Elqora\Dgp\Runtime\PreparationStatus;
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
use Elqora\Dgp\Runtime\References\PlanReference;
use Elqora\Dgp\Runtime\PlanStatus;
use Elqora\Dgp\Runtime\StartResultStatus;
use Elqora\Dgp\Actions\Contracts\NextAction;
use Elqora\Dgp\Actions\ActionButton;
use Elqora\Dgp\Actions\ActionButtonKind;
use Elqora\Dgp\Actions\ActionButtonStyle;
use Elqora\Dgp\Actions\ActionTarget;
use Elqora\Dgp\Actions\ActionTargetType;
use Elqora\Dgp\Actions\Contracts\GenericActionContract;
use Elqora\Dgp\Actions\GenericActionRequest;
use Elqora\Dgp\Actions\RedirectAction;
use Elqora\Dgp\Bulk\CancelBulkRequest;
use Elqora\Dgp\Bulk\Contracts\BulkActionContract;
use Elqora\Dgp\Bulk\RefreshBulkRequest;
use Elqora\Dgp\Bulk\RetryBulkRequest;
use Elqora\Dgp\Bulk\StartBulkRequest;
use Elqora\Dgp\Charges\Charge;
use Elqora\Dgp\Charges\ChargeStateRequest;
use Elqora\Dgp\Charges\ChargeStatusView;
use Elqora\Dgp\Charges\ChargeTarget;
use Elqora\Dgp\Charges\ChargeTargetType;
use Elqora\Dgp\Charges\ChargePaymentNotification;
use Elqora\Dgp\Charges\ChargePayment;
use Elqora\Dgp\Charges\ChargePaymentStatus;
use Elqora\Dgp\Charges\ChargeStatus;
use Elqora\Dgp\Money\Amount;
use Elqora\Dgp\Money\Currency;
use Elqora\Dgp\Money\Money;
use Elqora\Dgp\Configuration\Dgp;
use Elqora\Dgp\Deliveries\DeliveryProgress;
use Elqora\Dgp\Deliveries\DeliveryProgressSegment;
use Elqora\Dgp\Endpoints\HostEndpointType;
use Elqora\Dgp\Events\DgpEvent;
use Elqora\Dgp\Events\EventType;
use Elqora\ConfigKit\Contracts\ProvidesConfigSchema;
use Elqora\ConfigKit\Schema\ConfigSchema;
use Elqora\ConfigKit\Schema\UiConfigSchema;
use Elqora\ConfigKit\Support\ConfigBag;
use Elqora\ConfigKit\Support\ConfigValidationResult;
use Elqora\Dgp\Balance\BalanceRequest;
use Elqora\Dgp\Health\HealthRequest;
use InvalidArgumentException;

class DriverComplianceTest extends TestCase
{
    private function chartFixture(): Chart
    {
        return new Chart(
            key: 'delivery.throughput',
            type: ChartType::LINE,
            title: 'Delivery throughput',
            data: new TabularData(
                categoryField: 'time',
                rows: [
                    ['time' => '10:00', 'delivered' => 10],
                    ['time' => '10:30', 'delivered' => 25],
                ],
                series: [
                    new Series('delivered', 'Delivered', 'delivered', ValueType::INTEGER),
                ],
            ),
        );
    }

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

        $this->assertInstanceOf(BulkActionContract::class, $handler);
        $this->assertInstanceOf(GenericActionContract::class, $handler);
    }

    public function testManualHandlerCapabilityMatching(): void
    {
        $handler = new ManualTestHandler();
        $manifest = $handler->manifest()->value();

        $capabilities = $manifest->capabilities;

        $this->assertContains(Capability::SERVICE_SCHEMA_CATALOG, $capabilities);
        $this->assertInstanceOf(ServiceSchemaCatalogContract::class, $handler);
    }

    public function testHandlerServiceRoundTripAndCapabilityUpgrade(): void
    {
        $service = new HandlerService(
            id: 101,
            name: 'Premium delivery',
            description: 'Fast delivery service',
            category: 'delivery',
            rate: 12.5,
            min: 100,
            max: 10000,
            capabilities: new ServiceCapabilitySet(
                [
                    new ServiceCapability('refill', true, 'Supports refill.'),
                    new ServiceCapability('cancel', false, 'Cancellation unavailable.'),
                ]
            ),
            meta: new ServiceMeta(
                raw: ['provider_payload' => ['id' => '101']],
                derived: ['region' => 'global'],
            ),
        );

        $serialized = Hydrator::serialize($service);
        $this->assertEquals(12.5, $serialized['rate']);
        $this->assertEquals(100, $serialized['min']);
        $this->assertEquals(10000, $serialized['max']);
        $this->assertTrue($serialized['capabilities']['refill']['enabled']);
        $this->assertEquals('global', $serialized['meta']['derived']['region']);

        $hydrated = Hydrator::hydrate(HandlerService::class, $serialized);
        $this->assertTrue(Hydrator::compare($service, $hydrated));
        $this->assertTrue($hydrated->capabilities->enabled('refill'));
        $this->assertEquals('global', $hydrated->meta->getAny('region'));
    }

    public function testHandlerServiceLegacyConstructorInputStillWorks(): void
    {
        $service = new HandlerService(
            id: 101,
            name: 'Legacy service',
            category: 'delivery',
            capabilities: ['refill'],
            meta: ['region' => 'global'],
        );

        $this->assertNull($service->rate);
        $this->assertEquals(1, $service->min);
        $this->assertEquals(1, $service->max);
        $this->assertTrue($service->capabilities->enabled('refill'));
        $this->assertEquals('global', $service->meta->getAny('region'));
    }

    public function testServiceInsightsManifestRoundTrip(): void
    {
        $manifest = new HandlerManifest(
            key: 'smm-test',
            name: 'SMM Test',
            version: '1.0.0',
            capabilities: [Capability::SERVICE_INSIGHTS],
            analyses: [
                new AnalysisDefinition('delivery.throughput', 'Delivery throughput', 'Orders delivered over time.'),
            ],
            scoreboardItems: [
                new ScoreboardItemDefinition('delivery.success-rate', 'Success rate'),
            ],
            providesLeaderboard: true,
        );

        $serialized = Hydrator::serialize($manifest);

        $this->assertEquals('service_insights', $serialized['capabilities'][0]);
        $this->assertEquals('delivery.throughput', $serialized['analyses'][0]['key']);
        $this->assertEquals('delivery.success-rate', $serialized['scoreboard_items'][0]['key']);
        $this->assertTrue($serialized['provides_leaderboard']);

        $hydrated = Hydrator::hydrate(HandlerManifest::class, $serialized);
        $this->assertTrue(Hydrator::compare($manifest, $hydrated));
    }

    public function testServiceInsightsManifestRejectsDuplicateKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Analysis definition key values must be unique.');

        new HandlerManifest(
            key: 'smm-test',
            name: 'SMM Test',
            version: '1.0.0',
            analyses: [
                new AnalysisDefinition('delivery.throughput', 'Delivery throughput'),
                new AnalysisDefinition('delivery.throughput', 'Delivery throughput again'),
            ],
        );
    }

    public function testServiceInsightDtosRoundTripWithElqoraChart(): void
    {
        $analysis = new Analysis('delivery.throughput', $this->chartFixture());
        $scoreboard = new Scoreboard([
            new ScoreboardItem(
                key: 'delivery.success-rate',
                value: 98.5,
                title: 'Success rate',
                unit: '%',
            ),
        ]);
        $leaderboard = new Leaderboard([
            new LeaderboardEntry(
                serviceId: 101,
                rank: 1,
                score: 99.2,
                title: 'Premium delivery',
            ),
        ]);

        $hydratedAnalysis = Hydrator::hydrate(Analysis::class, Hydrator::serialize($analysis));
        $hydratedScoreboard = Hydrator::hydrate(Scoreboard::class, Hydrator::serialize($scoreboard));
        $hydratedLeaderboard = Hydrator::hydrate(Leaderboard::class, Hydrator::serialize($leaderboard));

        $this->assertTrue(Hydrator::compare($analysis, $hydratedAnalysis));
        $this->assertTrue(Hydrator::compare($scoreboard, $hydratedScoreboard));
        $this->assertTrue(Hydrator::compare($leaderboard, $hydratedLeaderboard));
    }

    public function testDeclaredInsightKeysMatchRuntimeUpdateKeys(): void
    {
        $manifest = new HandlerManifest(
            key: 'smm-test',
            name: 'SMM Test',
            version: '1.0.0',
            analyses: [
                new AnalysisDefinition('delivery.throughput', 'Delivery throughput'),
            ],
            scoreboardItems: [
                new ScoreboardItemDefinition('delivery.success-rate', 'Success rate'),
            ],
        );

        $analysis = new Analysis('delivery.throughput', $this->chartFixture());
        $scoreboard = new Scoreboard([
            new ScoreboardItem('delivery.success-rate', 98.5),
        ]);

        $declaredAnalyses = array_map(fn (AnalysisDefinition $definition) => $definition->key, $manifest->analyses);
        $declaredScoreboardItems = array_map(fn (ScoreboardItemDefinition $definition) => $definition->key, $manifest->scoreboardItems);

        $this->assertContains($analysis->analysisKey, $declaredAnalyses);
        $this->assertContains($scoreboard->items[0]->key, $declaredScoreboardItems);
    }

    public function testServiceInsightDtosRejectUnstableKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Scoreboard item key must be a stable identifier.');

        new ScoreboardItem('not a stable key', 10);
    }

    public function testPlanRoundTripAndStability(): void
    {
        $plan = new Plan(
            id: null,
            key: 'plan-123',
            state: ['reserved' => true],
            orderId: 123,
        );

        $serialized = Hydrator::serialize($plan);
        $this->assertNull($serialized['id']);
        $this->assertEquals('plan-123', $serialized['key']);
        $this->assertEquals(123, $serialized['order_id']);

        $hydrated = Hydrator::hydrate(Plan::class, $serialized);
        $this->assertTrue(Hydrator::compare($plan, $hydrated));
        $this->assertEquals(123, $hydrated->orderId);

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

    public function testDeliveryRenderingFieldsAndProgressHydration(): void
    {
        $init = new InitializationDelivery(
            id: null,
            key: 'init-1',
            status: DeliveryStatus::PROCESSING,
            label: 'Review',
            progress: 50,
            kind: 'admin_review',
            name: 'Admin Review',
            isPublic: false,
            note: 'Internal preparation'
        );

        $serialized = Hydrator::serialize($init);
        $this->assertEquals('admin_review', $serialized['kind']);
        $this->assertEquals('Admin Review', $serialized['name']);
        $this->assertFalse($serialized['is_public']);
        $this->assertEquals('Internal preparation', $serialized['note']);
        $this->assertEquals(50.0, $serialized['progress']['percent']);

        $hydrated = Hydrator::hydrate(InitializationDelivery::class, [
            ...$serialized,
            'progress' => [
                'current' => 25,
                'target' => 100,
                'percent' => 25,
                'unit' => 'items',
                'label' => '25 of 100',
            ],
        ]);

        $this->assertInstanceOf(DeliveryProgress::class, $hydrated->progress);
        $this->assertEquals(25, $hydrated->progress->current);
        $this->assertEquals(100, $hydrated->progress->target);
        $this->assertEquals('items', $hydrated->progress->unit);
    }

    public function testDeliveryProgressSegmentsConstructHydrateAndSerialize(): void
    {
        $progress = new DeliveryProgress(
            current: 50,
            target: 100,
            percent: 50,
            unit: 'items',
            segments: [
                new DeliveryProgressSegment(
                    key: 'phase-1',
                    progress: new DeliveryProgress(current: 25, target: 50, percent: 50, unit: 'items'),
                    label: 'Phase 1',
                    status: 'processing',
                    sequence: 1,
                    meta: ['provider_batch' => 'batch-1'],
                    isPublic: true
                ),
            ]
        );

        $serialized = $progress->toArray();

        $this->assertEquals(50.0, $serialized['percent']);
        $this->assertCount(1, $serialized['segments']);
        $this->assertEquals('phase-1', $serialized['segments'][0]['key']);
        $this->assertEquals('Phase 1', $serialized['segments'][0]['label']);
        $this->assertEquals('processing', $serialized['segments'][0]['status']);
        $this->assertEquals(1, $serialized['segments'][0]['sequence']);
        $this->assertEquals(['provider_batch' => 'batch-1'], $serialized['segments'][0]['meta']);
        $this->assertTrue($serialized['segments'][0]['is_public']);
        $this->assertEquals(25, $serialized['segments'][0]['progress']['current']);

        $this->assertEquals($serialized, $progress->jsonSerialize());
        $this->assertEquals($serialized, json_decode((string) json_encode($progress), true));
    }

    public function testDeliveryProgressHydratesSegmentArraysWithoutNestedSegments(): void
    {
        $progress = DeliveryProgress::fromValue([
            'current' => 80,
            'target' => 100,
            'percent' => 80,
            'unit' => 'items',
            'segments' => [
                [
                    'key' => 'created',
                    'progress' => [
                        'current' => 30,
                        'target' => 100,
                        'percent' => 30,
                        'unit' => 'items',
                        'segments' => [
                            [
                                'key' => 'nested',
                                'progress' => ['percent' => 10],
                            ],
                        ],
                    ],
                    'label' => 'Created',
                    'status' => 'complete',
                    'sequence' => 1,
                ],
                [
                    'key' => 'delivered',
                    'progress' => ['current' => 50, 'target' => 100, 'percent' => 50],
                    'label' => 'Delivered',
                    'status' => 'processing',
                    'sequence' => 2.5,
                ],
            ],
        ]);

        $this->assertInstanceOf(DeliveryProgress::class, $progress);
        $this->assertCount(2, $progress->segments);
        $this->assertContainsOnlyInstancesOf(DeliveryProgressSegment::class, $progress->segments);
        $this->assertEquals('created', $progress->segments[0]->key);
        $this->assertEquals(30.0, $progress->segments[0]->progress->percent);
        $this->assertSame([], $progress->segments[0]->progress->segments);
        $this->assertEquals('delivered', $progress->segments[1]->key);
        $this->assertEquals(2.5, $progress->segments[1]->sequence);
    }

    public function testDeliveryProgressHydratorSupportsSegmentsAndEmptyDefaults(): void
    {
        $empty = new DeliveryProgress(percent: 0);

        $this->assertSame([], $empty->segments);
        $this->assertSame([], $empty->toArray()['segments']);

        $hydrated = Hydrator::hydrate(DeliveryProgress::class, [
            'current' => 10,
            'target' => 20,
            'percent' => 50,
            'segments' => [
                [
                    'key' => 'first-half',
                    'progress' => ['current' => 10, 'target' => 20, 'percent' => 50],
                    'label' => 'First half',
                    'status' => 'complete',
                    'meta' => ['source' => 'sync'],
                ],
            ],
        ]);

        $this->assertInstanceOf(DeliveryProgress::class, $hydrated);
        $this->assertCount(1, $hydrated->segments);
        $this->assertInstanceOf(DeliveryProgressSegment::class, $hydrated->segments[0]);
        $this->assertEquals('first-half', $hydrated->segments[0]->key);
        $this->assertEquals(50.0, $hydrated->segments[0]->progress->percent);
        $this->assertEquals(['source' => 'sync'], $hydrated->segments[0]->meta);
    }

    public function testTypedHostEndpointResolutionKeepsGenericPath(): void
    {
        Dgp::endpointPrefix('/dgp');

        $endpoint = Dgp::endpoint('smm-test', HostEndpointType::DELIVERY_ACTION);
        $this->assertSame(HostEndpointType::DELIVERY_ACTION, $endpoint->type);
        $this->assertEquals('/dgp/smm-test/delivery/action', $endpoint->path);

        $generic = Dgp::endpoint('smm-test', HostEndpointType::GENERIC_ACTION);
        $this->assertSame(HostEndpointType::GENERIC_ACTION, $generic->type);
        $this->assertEquals('/dgp/smm-test/generic/action', $generic->path);

        $bulk = Dgp::endpoint('smm-test', HostEndpointType::BULK_ACTION);
        $this->assertSame(HostEndpointType::BULK_ACTION, $bulk->type);
        $this->assertEquals('/dgp/smm-test/bulk/action', $bulk->path);

        $chargePayment = Dgp::endpoint('smm-test', HostEndpointType::CHARGE_PAYMENT);
        $this->assertSame(HostEndpointType::CHARGE_PAYMENT, $chargePayment->type);
        $this->assertEquals('/dgp/smm-test/charge/payment', $chargePayment->path);

        $asset = Dgp::endpoint('smm-test', HostEndpointType::PRIVATE_ASSET, 'invoice.pdf');
        $this->assertEquals('/dgp/smm-test/assets/invoice.pdf', $asset->path);
        $this->assertEquals('invoice.pdf', $asset->parameters['asset']);

        $this->assertEquals('/dgp/smm-test/custom/action', Dgp::path('smm-test', 'custom/action'));
    }

    public function testDgpEventSupportsBuiltInEnumAndCustomStringTypes(): void
    {
        $builtIn = new DgpEvent(
            id: 'event-1',
            type: EventType::INITIALIZED,
            handlerKey: 'smm-test',
            orderId: 123
        );
        $this->assertEquals('initialized', $builtIn->toArray()['type']);

        $custom = new DgpEvent(
            id: 'event-2',
            type: 'provider.custom_event',
            handlerKey: 'smm-test',
            orderId: 123
        );
        $this->assertEquals('provider.custom_event', $custom->toArray()['type']);
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
            orderId: 123,
            plan: new PlanReference(id: 'plan-123'),
            context: new RuntimeContext(
                context: ['foo' => 'bar']
            ),
            meta: ['source' => 'admin']
        );

        $serialized = Hydrator::serialize($request);
        $this->assertEquals(123, $serialized['order_id']);
        $this->assertEquals('plan-123', $serialized['plan']['id']);

        $hydrated = Hydrator::hydrate(StartRequest::class, $serialized);
        $this->assertEquals(123, $hydrated->orderId);
        $this->assertEquals('plan-123', $hydrated->plan->id);
        $this->assertNotNull($hydrated->context);
        $this->assertEquals(['foo' => 'bar'], $hydrated->context->context);
        $this->assertEquals(['source' => 'admin'], $hydrated->meta);
    }

    public function testPrepareRequestRoundTripAndValidation(): void
    {
        $delivery = new InitializationDelivery(
            id: 201,
            key: 'probe',
            status: DeliveryStatus::PENDING,
            label: 'Probe allocation',
            planId: 41
        );

        $plan = new Plan(
            id: 41,
            key: 'smm-plan',
            state: ['allocation' => 'probe'],
            deliveries: [$delivery],
            revision: 3,
            orderId: 123,
            status: PlanStatus::ACTIVE
        );

        $request = new PrepareRequest(
            orderId: 123,
            plan: $plan,
            context: new RuntimeContext(context: ['worker' => 'prep-1']),
            meta: ['source' => 'queue']
        );

        $serialized = Hydrator::serialize($request);
        $this->assertEquals(123, $serialized['order_id']);
        $this->assertEquals(41, $serialized['plan']['id']);
        $this->assertEquals(201, $serialized['plan']['deliveries'][0]['id']);

        $hydrated = Hydrator::hydrate(PrepareRequest::class, $serialized);
        $this->assertEquals(123, $hydrated->orderId);
        $this->assertEquals(41, $hydrated->plan->id);
        $this->assertEquals(201, $hydrated->plan->deliveries[0]->id);
        $this->assertEquals(['worker' => 'prep-1'], $hydrated->context?->context);
        $this->assertEquals(['source' => 'queue'], $hydrated->meta);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Preparation requires a persisted plan ID.');
        new PrepareRequest(
            orderId: 123,
            plan: new Plan(null, 'transient-plan', [])
        );
    }

    public function testPrepareRequestRejectsUnpersistedDeliveries(): void
    {
        $plan = new Plan(
            id: 41,
            key: 'smm-plan',
            state: [],
            deliveries: [
                new InitializationDelivery(
                    id: null,
                    key: 'probe',
                    status: DeliveryStatus::PENDING,
                    label: 'Probe allocation',
                    planId: 41
                ),
            ]
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Preparation requires persisted delivery IDs.');
        new PrepareRequest(orderId: 123, plan: $plan);
    }

    public function testPreparationResultRoundTripAndDeliveryPreservation(): void
    {
        $delivery = new InitializationDelivery(
            id: 201,
            key: 'probe',
            status: DeliveryStatus::PROCESSING,
            label: 'Probe allocation',
            progress: new DeliveryProgress(current: 1, target: 2, percent: 50, unit: 'segments'),
            planId: 41,
            meta: ['provider_order_id' => '918273']
        );

        $result = new PreparationResult(
            planId: 41,
            status: PreparationStatus::RUNNING,
            deliveries: [$delivery],
            state: ['claimed' => true],
            meta: ['attempt' => 1]
        );

        $serialized = Hydrator::serialize($result);
        $this->assertEquals(41, $serialized['plan_id']);
        $this->assertEquals('running', $serialized['status']);
        $this->assertEquals(201, $serialized['deliveries'][0]['id']);
        $this->assertEquals(41, $serialized['deliveries'][0]['plan_id']);

        $hydrated = Hydrator::hydrate(PreparationResult::class, $serialized);
        $this->assertEquals(41, $hydrated->planId);
        $this->assertEquals(PreparationStatus::RUNNING, $hydrated->status);
        $this->assertEquals(201, $hydrated->deliveries[0]->id);
        $this->assertEquals(41, $hydrated->deliveries[0]->planId);
        $this->assertEquals('918273', $hydrated->deliveries[0]->meta['provider_order_id']);
    }

    public function testHandlerPreparationUpdatesInitializationDeliveriesOnly(): void
    {
        $handler = new SmmTestHandler();
        $plan = new Plan(
            id: 41,
            key: 'smm-plan',
            state: ['allocation' => 'probe'],
            deliveries: [
                new InitializationDelivery(
                    id: 201,
                    key: 'probe',
                    status: DeliveryStatus::PENDING,
                    label: 'Probe allocation',
                    planId: 41,
                    meta: ['segment_key' => 'probe-a']
                ),
            ],
            revision: 1,
            orderId: 123
        );

        $result = $handler->prepare(new PrepareRequest(orderId: 123, plan: $plan))->value();

        $this->assertEquals(41, $result->planId);
        $this->assertEquals(PreparationStatus::RUNNING, $result->status);
        $this->assertCount(1, $result->deliveries);
        $this->assertInstanceOf(InitializationDelivery::class, $result->deliveries[0]);
        $this->assertEquals(201, $result->deliveries[0]->id);
        $this->assertEquals('probe', $result->deliveries[0]->key);
        $this->assertEquals(41, $result->deliveries[0]->planId);
        $this->assertNull($result->deliveries[0]->startId);
        $this->assertEquals(DeliveryStatus::PROCESSING, $result->deliveries[0]->status);
        $this->assertTrue($result->deliveries[0]->meta['prepared']);
        $this->assertEquals('probe-a', $result->deliveries[0]->meta['segment_key']);
    }

    public function testGenericActionRequestRoundTrip(): void
    {
        $request = new GenericActionRequest(
            handlerKey: 'smm-test',
            actionValue: 'retry_selected',
            targets: [
                new ActionTarget(ActionTargetType::ORDER, 123),
                new ActionTarget(ActionTargetType::CHARGE, 456, key: 'deposit'),
                new ActionTarget('provider.custom_target', 'target-1'),
            ],
            input: ['reason' => 'manual_retry'],
            context: new RuntimeContext(context: ['actor' => 'admin']),
            meta: ['trace' => 'bulk-1']
        );

        $serialized = Hydrator::serialize($request);
        $this->assertEquals('retry_selected', $serialized['action_value']);
        $this->assertEquals('order', $serialized['targets'][0]['type']);
        $this->assertEquals('charge', $serialized['targets'][1]['type']);
        $this->assertEquals('provider.custom_target', $serialized['targets'][2]['type']);

        $hydrated = Hydrator::hydrate(GenericActionRequest::class, $serialized);
        $this->assertEquals('smm-test', $hydrated->handlerKey);
        $this->assertCount(3, $hydrated->targets);
        $this->assertEquals('charge', $hydrated->targets[1]->type);
        $this->assertEquals(['actor' => 'admin'], $hydrated->context?->context);
    }

    public function testExplicitBulkRequestRoundTrips(): void
    {
        $targets = [
            new ActionTarget(ActionTargetType::ORDER, 123),
            new ActionTarget(ActionTargetType::PLAN, 456),
        ];

        $start = new StartBulkRequest(
            handlerKey: 'smm-test',
            targets: $targets,
            input: ['source' => 'admin'],
            context: new RuntimeContext(context: ['actor' => 'admin'])
        );

        $hydrated = Hydrator::hydrate(StartBulkRequest::class, Hydrator::serialize($start));
        $this->assertEquals('smm-test', $hydrated->handlerKey);
        $this->assertCount(2, $hydrated->targets);
        $this->assertEquals('plan', $hydrated->targets[1]->type);

        $handler = new SmmTestHandler();
        $this->assertTrue($handler->startBulk($start)->isSuccess());
        $this->assertTrue($handler->cancelBulk(new CancelBulkRequest('smm-test', $targets))->isSuccess());
        $this->assertTrue($handler->retryBulk(new RetryBulkRequest('smm-test', $targets))->isSuccess());
        $this->assertTrue($handler->refreshBulk(new RefreshBulkRequest('smm-test', $targets))->isSuccess());
    }

    public function testActionButtonsRoundTripOnRuntimeObjects(): void
    {
        $buttons = [
            new ActionButton(
                value: 'cancel',
                label: 'Cancel',
                style: ActionButtonStyle::DANGER
            ),
            new ActionButton(
                value: 'refresh',
                kind: ActionButtonKind::ICON,
                icon: 'refresh-cw',
                tooltip: 'Refresh'
            ),
        ];

        $plan = new Plan(
            id: null,
            key: 'plan-actions',
            state: [],
            buttons: $buttons
        );
        $planPayload = Hydrator::serialize($plan);
        $this->assertCount(2, $planPayload['buttons']);
        $this->assertEquals('text', $planPayload['buttons'][0]['kind']);
        $this->assertEquals('icon', $planPayload['buttons'][1]['kind']);
        $this->assertTrue(Hydrator::compare($plan, Hydrator::hydrate(Plan::class, $planPayload)));

        $preparation = new PreparationResult(
            planId: 41,
            status: PreparationStatus::RUNNING,
            buttons: $buttons
        );
        $this->assertTrue(Hydrator::compare($preparation, Hydrator::hydrate(PreparationResult::class, $preparation->toArray())));

        $start = new StartResult(
            id: null,
            key: 'start-actions',
            state: [],
            buttons: $buttons
        );
        $this->assertTrue(Hydrator::compare($start, Hydrator::hydrate(StartResult::class, $start->toArray())));

        $initialization = new InitializationDelivery(
            id: null,
            key: 'init-actions',
            status: DeliveryStatus::PENDING,
            label: 'Initialize',
            buttons: $buttons
        );
        $this->assertTrue(Hydrator::compare($initialization, Hydrator::hydrate(InitializationDelivery::class, $initialization->toArray())));

        $fulfillment = new FulfillmentDelivery(
            id: null,
            key: 'fulfill-actions',
            status: DeliveryStatus::PENDING,
            label: 'Fulfill',
            buttons: [
                new ActionButton(value: 'retry', label: 'Retry'),
            ]
        );
        $this->assertTrue(Hydrator::compare($fulfillment, Hydrator::hydrate(FulfillmentDelivery::class, $fulfillment->toArray())));

        $charge = new Charge(
            id: null,
            key: 'charge-actions',
            target: new ChargeTarget(ChargeTargetType::PLAN, key: 'plan-actions'),
            label: 'Payment',
            amount: new Money(new Amount('10.00'), new Currency('USD')),
            status: ChargeStatus::PENDING,
            buttons: $buttons
        );
        $this->assertTrue(Hydrator::compare($charge, Hydrator::hydrate(Charge::class, $charge->toArray())));
    }

    public function testActionButtonSupportsNestedNextActionWithReservedValue(): void
    {
        $redirect = new RedirectAction(
            url: 'https://gateway.example/pay',
            external: true,
            label: 'Open payment'
        );

        $button = new ActionButton(
            value: 'action',
            label: 'Open payment',
            nextAction: $redirect
        );

        $serialized = Hydrator::serialize($button);
        $this->assertEquals('redirect', $serialized['next_action']['type']);

        $hydrated = Hydrator::hydrate(ActionButton::class, $serialized);
        $this->assertInstanceOf(RedirectAction::class, $hydrated->nextAction);
        $this->assertTrue(Hydrator::compare($button, $hydrated));
    }

    public function testActionButtonsRejectInvalidPayloads(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Text buttons require a label.');

        new ActionButton(
            value: 'approve',
            kind: ActionButtonKind::TEXT
        );
    }

    public function testActionButtonRejectsNextActionWithoutReservedValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Buttons with a next action must use value "action".');

        new ActionButton(
            value: 'pay',
            label: 'Pay',
            nextAction: new RedirectAction(url: 'https://gateway.example/pay')
        );
    }

    public function testActionButtonRejectsReservedValueWithoutNextAction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Button value "action" requires a next action.');

        new ActionButton(
            value: 'action',
            label: 'Open payment'
        );
    }

    public function testLegacyButtonNextActionPayloadHydratesToParentButtons(): void
    {
        $hydrated = Hydrator::hydrate(Plan::class, [
            'id' => null,
            'key' => 'legacy-plan',
            'state' => [],
            'next_action' => [
                'type' => 'button',
                'label' => 'Available actions',
                'buttons' => [
                    [
                        'value' => 'approve',
                        'kind' => 'text',
                        'label' => 'Approve',
                        'style' => 'primary',
                    ],
                ],
                'meta' => [],
            ],
        ]);

        $this->assertNull($hydrated->nextAction);
        $this->assertCount(1, $hydrated->buttons);
        $this->assertInstanceOf(ActionButton::class, $hydrated->buttons[0]);
        $this->assertEquals('approve', $hydrated->buttons[0]->value);
    }

    public function testLegacyButtonNextActionPayloadDoesNotOverrideTopLevelButtons(): void
    {
        $hydrated = Hydrator::hydrate(Plan::class, [
            'id' => null,
            'key' => 'legacy-plan',
            'state' => [],
            'buttons' => [
                [
                    'value' => 'retry',
                    'kind' => 'text',
                    'label' => 'Retry',
                ],
            ],
            'next_action' => [
                'type' => 'button',
                'buttons' => [
                    [
                        'value' => 'approve',
                        'kind' => 'text',
                        'label' => 'Approve',
                    ],
                ],
            ],
        ]);

        $this->assertNull($hydrated->nextAction);
        $this->assertCount(1, $hydrated->buttons);
        $this->assertEquals('retry', $hydrated->buttons[0]->value);
    }

    public function testChargePaymentHistoryRoundTrip(): void
    {
        $payment = new ChargePayment(
            key: 'payment-1',
            amount: new Money(new Amount('25.00'), new Currency('USD')),
            status: ChargePaymentStatus::PAID,
            paidAt: '2026-07-09T10:00:00Z',
            method: 'wallet',
            reference: 'txn-123',
            meta: ['gateway' => 'internal']
        );

        $charge = new Charge(
            id: null,
            key: 'deposit',
            target: new ChargeTarget(ChargeTargetType::PLAN, key: 'plan-payment'),
            label: 'Deposit',
            amount: new Money(new Amount('100.00'), new Currency('USD')),
            status: ChargeStatus::PARTIALLY_PAID,
            paidAmount: new Money(new Amount('25.00'), new Currency('USD')),
            balanceDue: new Money(new Amount('75.00'), new Currency('USD')),
            payments: [$payment]
        );

        $serialized = Hydrator::serialize($charge);
        $this->assertCount(1, $serialized['payments']);
        $this->assertEquals('paid', $serialized['payments'][0]['status']);
        $this->assertEquals('txn-123', $serialized['payments'][0]['reference']);
        $this->assertEquals('plan', $serialized['target']['type']);
        $this->assertEquals('plan-payment', $serialized['target']['key']);
        $this->assertArrayNotHasKey('delivery_key', $serialized);

        $hydrated = Hydrator::hydrate(Charge::class, $serialized);
        $this->assertTrue(Hydrator::compare($charge, $hydrated));
        $this->assertSame(ChargePaymentStatus::PAID, $hydrated->payments[0]->status);
        $this->assertInstanceOf(ChargeTarget::class, $hydrated->target);
        $this->assertSame(ChargeTargetType::PLAN, $hydrated->target->type);
    }

    public function testChargeTargetSupportsNestedAndCustomWorkflowOwnership(): void
    {
        $segmentTarget = new ChargeTarget(
            type: ChargeTargetType::SEGMENT,
            key: 'approval',
            parent: new ChargeTarget(ChargeTargetType::DELIVERY, key: 'fulfillment'),
            meta: ['phase' => 'preflight']
        );

        $segmentCharge = new Charge(
            id: 7,
            key: 'approval-fee',
            target: $segmentTarget,
            label: 'Approval Fee',
            amount: new Money(new Amount('10.00'), new Currency('USD')),
            status: ChargeStatus::PENDING
        );

        $customCharge = new Charge(
            id: 8,
            key: 'future-fee',
            target: new ChargeTarget('workflow.custom_checkpoint', key: 'checkpoint-1'),
            label: 'Future Fee',
            amount: new Money(new Amount('15.00'), new Currency('USD')),
            status: ChargeStatus::PENDING
        );

        $serializedSegment = Hydrator::serialize($segmentCharge);
        $this->assertEquals('segment', $serializedSegment['target']['type']);
        $this->assertEquals('delivery', $serializedSegment['target']['parent']['type']);
        $this->assertEquals('fulfillment', $serializedSegment['target']['parent']['key']);
        $this->assertEquals(['phase' => 'preflight'], $serializedSegment['target']['meta']);

        $hydratedSegment = Hydrator::hydrate(Charge::class, $serializedSegment);
        $this->assertTrue(Hydrator::compare($segmentCharge, $hydratedSegment));
        $this->assertInstanceOf(ChargeTarget::class, $hydratedSegment->target);
        $this->assertSame(ChargeTargetType::SEGMENT, $hydratedSegment->target->type);
        $this->assertInstanceOf(ChargeTarget::class, $hydratedSegment->target->parent);
        $this->assertSame(ChargeTargetType::DELIVERY, $hydratedSegment->target->parent->type);

        $serializedCustom = Hydrator::serialize($customCharge);
        $this->assertEquals('workflow.custom_checkpoint', $serializedCustom['target']['type']);
        $hydratedCustom = Hydrator::hydrate(Charge::class, $serializedCustom);
        $this->assertTrue(Hydrator::compare($customCharge, $hydratedCustom));
        $this->assertInstanceOf(ChargeTarget::class, $hydratedCustom->target);
        $this->assertSame('workflow.custom_checkpoint', $hydratedCustom->target->type);
    }

    public function testChargeTargetRequiresIdentity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A charge target ID or key is required.');

        new ChargeTarget(ChargeTargetType::PLAN);
    }

    public function testChargeStateDtosCarryGenericTargets(): void
    {
        $target = new ChargeTarget(ChargeTargetType::DELIVERY, key: 'fulfillment');
        $statusView = new ChargeStatusView(
            id: 7,
            key: 'fulfillment-fee',
            status: ChargeStatus::PAID,
            amount: new Money(new Amount('50.00'), new Currency('USD')),
            paid: new Money(new Amount('50.00'), new Currency('USD')),
            balanceDue: new Money(new Amount('0.00'), new Currency('USD')),
            satisfied: true,
            paidAt: '2026-07-09T10:00:00Z',
            target: $target
        );
        $request = new ChargeStateRequest(
            orderId: 12345,
            chargeKey: 'fulfillment-fee',
            target: $target
        );

        $serializedStatus = Hydrator::serialize($statusView);
        $this->assertEquals('delivery', $serializedStatus['target']['type']);
        $this->assertEquals('fulfillment', $serializedStatus['target']['key']);

        $hydratedStatus = Hydrator::hydrate(ChargeStatusView::class, $serializedStatus);
        $this->assertTrue(Hydrator::compare($statusView, $hydratedStatus));
        $this->assertInstanceOf(ChargeTarget::class, $hydratedStatus->target);
        $this->assertSame(ChargeTargetType::DELIVERY, $hydratedStatus->target->type);

        $serializedRequest = Hydrator::serialize($request);
        $this->assertEquals('delivery', $serializedRequest['target']['type']);
        $this->assertArrayNotHasKey('delivery_key', $serializedRequest);

        $hydratedRequest = Hydrator::hydrate(ChargeStateRequest::class, $serializedRequest);
        $this->assertTrue(Hydrator::compare($request, $hydratedRequest));
        $this->assertInstanceOf(ChargeTarget::class, $hydratedRequest->target);
        $this->assertSame(ChargeTargetType::DELIVERY, $hydratedRequest->target->type);
    }

    public function testChargePaymentNotificationRoundTrip(): void
    {
        $notification = new ChargePaymentNotification(
            orderId: 12345,
            chargeKey: 'deposit',
            paymentKey: 'payment-1',
            amount: new Money(new Amount('25.00'), new Currency('USD')),
            status: ChargePaymentStatus::PAID,
            occurredAt: '2026-07-09T10:00:01Z',
            chargeId: 77,
            paymentId: 'host-payment-1',
            paidAt: '2026-07-09T10:00:00Z',
            resultingChargeStatus: ChargeStatus::PARTIALLY_PAID,
            chargeTarget: new ChargeTarget(ChargeTargetType::PLAN, key: 'plan-payment'),
            context: ['gateway_event' => 'payment.succeeded'],
            meta: ['gateway' => 'internal'],
            notificationId: 'notification-1',
            source: 'host'
        );

        $serialized = Hydrator::serialize($notification);
        $this->assertEquals(12345, $serialized['order_id']);
        $this->assertEquals('deposit', $serialized['charge_key']);
        $this->assertEquals('payment-1', $serialized['payment_key']);
        $this->assertEquals(['amount' => '25.00', 'currency' => 'USD'], $serialized['amount']);
        $this->assertEquals('paid', $serialized['status']);
        $this->assertEquals('partially_paid', $serialized['resulting_charge_status']);
        $this->assertEquals('plan', $serialized['charge_target']['type']);
        $this->assertEquals('plan-payment', $serialized['charge_target']['key']);
        $this->assertEquals(['gateway_event' => 'payment.succeeded'], $serialized['context']);
        $this->assertEquals('notification-1', $serialized['notification_id']);

        $hydrated = Hydrator::hydrate(ChargePaymentNotification::class, $serialized);
        $this->assertTrue(Hydrator::compare($notification, $hydrated));
        $this->assertSame(ChargePaymentStatus::PAID, $hydrated->status);
        $this->assertSame(ChargeStatus::PARTIALLY_PAID, $hydrated->resultingChargeStatus);
        $this->assertInstanceOf(ChargeTarget::class, $hydrated->chargeTarget);
        $this->assertSame(ChargeTargetType::PLAN, $hydrated->chargeTarget->type);
    }

    public function testPaymentNotificationContractIsOptIn(): void
    {
        $handler = new PaymentNotificationTestHandler();
        $notification = new ChargePaymentNotification(
            orderId: 12345,
            chargeKey: 'deposit',
            paymentKey: 'payment-1',
            amount: new Money(new Amount('25.00'), new Currency('USD')),
            status: ChargePaymentStatus::PAID,
            occurredAt: '2026-07-09T10:00:01Z'
        );

        $result = $handler->notifyPayment($notification);

        $this->assertTrue($result->isSuccess());
        $this->assertSame($notification, $handler->lastPaymentNotification);
        $this->assertFalse(method_exists(new ManualTestHandler(), 'notifyPayment'));
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

    public function testDeliveryNextActionIntegration(): void
    {
        $redirectAction = new RedirectAction(
            url: 'https://gateway.example/download/file-abc',
            external: true,
            label: 'Download Key'
        );

        // 1. Delivery construction with no next action
        $initNoAction = new InitializationDelivery(
            id: null,
            key: 'del-1',
            status: DeliveryStatus::PENDING,
            label: 'Task 1'
        );
        $this->assertNull($initNoAction->nextAction);
        $this->assertNull($initNoAction->toArray()['next_action']);

        // 2. Delivery construction with a next action
        $initWithAction = new InitializationDelivery(
            id: null,
            key: 'del-2',
            status: DeliveryStatus::PROCESSING,
            label: 'Task 2',
            progress: 50,
            planId: 'plan-123',
            startId: null,
            nextAction: $redirectAction
        );
        $this->assertSame($redirectAction, $initWithAction->nextAction);

        // 3. toArray() serialization
        $serialized = $initWithAction->toArray();
        $this->assertNotNull($serialized['next_action']);
        $this->assertEquals('redirect', $serialized['next_action']['type']);
        $this->assertEquals('https://gateway.example/download/file-abc', $serialized['next_action']['url']);

        // 4. Hydration round-trip
        $hydrated = Hydrator::hydrate(InitializationDelivery::class, $serialized);
        $this->assertInstanceOf(InitializationDelivery::class, $hydrated);
        $this->assertNotNull($hydrated->nextAction);
        $this->assertInstanceOf(RedirectAction::class, $hydrated->nextAction);
        $action = $hydrated->nextAction;
        $this->assertEquals('redirect', $action->type());
        $this->assertEquals('https://gateway.example/download/file-abc', $action->url);

        // 5. FulfillmentDelivery construct and parent-constructor forwarding
        $fulfillment = new FulfillmentDelivery(
            id: null,
            key: 'del-3',
            status: DeliveryStatus::COMPLETED,
            label: 'Task 3',
            progress: null,
            planId: null,
            startId: 'start-abc',
            nextAction: $redirectAction
        );
        $this->assertSame($redirectAction, $fulfillment->nextAction);
    }

    public function testBalanceContractSignatureAndHandlerImplementations(): void
    {
        $manual = new ManualTestHandler();
        $smm = new SmmTestHandler();

        $bag = new ConfigBag(false, ['base_url' => 'https://api.smm.example'], ['api_key' => 'secret']);
        $request = new BalanceRequest(config: $bag, meta: ['trace' => true]);

        $resManual = $manual->balance($request);
        $this->assertTrue($resManual->isSuccess());

        $resSmm = $smm->balance($request);
        $this->assertTrue($resSmm->isSuccess());
        $this->assertEquals('finite', $resSmm->value()->kind->value);
    }

    public function testHealthContractSignatureAndHandlerImplementations(): void
    {
        $manual = new ManualTestHandler();
        $smm = new SmmTestHandler();

        $bag = new ConfigBag(false, ['base_url' => 'https://api.smm.example'], ['api_key' => 'secret']);
        $request = new HealthRequest(config: $bag, meta: ['trace' => true]);

        $resManual = $manual->health($request);
        $this->assertTrue($resManual->isSuccess());

        $resSmm = $smm->health($request);
        $this->assertTrue($resSmm->isSuccess());
        $this->assertEquals('ok', $resSmm->value()->status->value);
    }

    public function testPlanAndStartResultStatusDtoVerification(): void
    {
        // 1. Plan status defaults and serialization
        $plan = new Plan(
            id: null,
            key: 'main-plan',
            state: []
        );
        $this->assertEquals(PlanStatus::ACTIVE, $plan->status);
        $this->assertEquals('active', $plan->toArray()['status']);

        // 2. Plan custom status serialization
        $planCancelled = new Plan(
            id: null,
            key: 'main-plan',
            state: [],
            status: PlanStatus::CANCELLED
        );
        $this->assertEquals('cancelled', $planCancelled->toArray()['status']);

        // 3. Plan hydration
        $hydratedPlan = Hydrator::hydrate(Plan::class, $planCancelled->toArray());
        $this->assertInstanceOf(Plan::class, $hydratedPlan);
        $this->assertEquals(PlanStatus::CANCELLED, $hydratedPlan->status);

        // 4. StartResult status defaults and serialization
        $start = new StartResult(
            id: null,
            key: 'start-1',
            state: []
        );
        $this->assertEquals(StartResultStatus::RUNNING, $start->status);
        $this->assertEquals('running', $start->toArray()['status']);

        // 5. StartResult custom status serialization
        $startCompleted = new StartResult(
            id: null,
            key: 'start-1',
            state: [],
            status: StartResultStatus::COMPLETED
        );
        $this->assertEquals('completed', $startCompleted->toArray()['status']);

        // 6. StartResult hydration
        $hydratedStart = Hydrator::hydrate(StartResult::class, $startCompleted->toArray());
        $this->assertInstanceOf(StartResult::class, $hydratedStart);
        $this->assertEquals(StartResultStatus::COMPLETED, $hydratedStart->status);
    }
}
