<?php

namespace Elqora\Dgp\Tests\Compliance;

use Elqora\Dgp\Catalog\Schemas\ProductDefinition;
use Elqora\Dgp\Catalog\Schemas\ProductDefinitionExtractor;
use Elqora\Dgp\Catalog\Schemas\ProductDefinitionHydrator;
use Elqora\Dgp\Catalog\Schemas\ProductDefinitionValidator;
use Elqora\Dgp\Catalog\Services\HandlerService;
use Elqora\Dgp\Catalog\Services\ServiceCapability;
use Elqora\Dgp\Catalog\Services\ServiceMeta;
use Elqora\Dgp\Support\Hydrator;
use JsonSerializable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SpecContractConformanceTest extends TestCase
{
    public function testServiceCapabilityHydratesAndSerializesLosslessly(): void
    {
        $fixture = self::fixture('service-capability.json');
        $capability = Hydrator::hydrate(ServiceCapability::class, $fixture);

        $this->assertInstanceOf(ServiceCapability::class, $capability);
        $this->assertSame($fixture, $capability->toArray());
        self::assertCanonicalJson('service-capability.json', $capability);
    }

    public function testHandlerServiceHydratesAndSerializesLosslessly(): void
    {
        $fixture = self::fixture('handler-service.json');
        $service = Hydrator::hydrate(HandlerService::class, $fixture);

        $this->assertInstanceOf(HandlerService::class, $service);
        $this->assertSame($fixture, $service->toArray());
        self::assertCanonicalJson('handler-service.json', $service);
    }

    public function testInternalServiceMetaLayersRemainAnOpaqueWireObject(): void
    {
        $meta = new ServiceMeta(
            raw: ['provider_payload' => ['id' => '101'], 'priority' => 'provider'],
            derived: ['region' => 'global', 'priority' => 'host'],
        );

        $this->assertSame([
            'provider_payload' => ['id' => '101'],
            'priority' => 'host',
            'region' => 'global',
        ], $meta->toArray());
        $this->assertSame(
            '{"provider_payload":{"id":"101"},"priority":"host","region":"global"}',
            json_encode($meta, JSON_THROW_ON_ERROR),
        );

        $hostDefinedKeys = ['raw' => ['kept' => true], 'derived' => ['also_kept' => true]];
        $this->assertSame($hostDefinedKeys, ServiceMeta::from($hostDefinedKeys)->toArray());
    }

    public function testProductDefinitionHydratesAndSerializesLosslessly(): void
    {
        $fixture = self::fixture('product-definition.json');
        $definition = ProductDefinitionHydrator::hydrate($fixture);

        $this->assertInstanceOf(ProductDefinition::class, $definition);
        $this->assertSame($fixture, ProductDefinitionExtractor::extract($definition));
        $this->assertSame([], ProductDefinitionValidator::validate($fixture));
        self::assertCanonicalJson('product-definition.json', $definition);
    }

    public function testProductDefinitionBindingPreservesExplicitVariantAndMultipleSelection(): void
    {
        $fixture = self::fixture('product-definition.json');
        $fixture['fields'][0]['variant'] = 'compact';
        $fixture['fields'][0]['multiple'] = true;

        $definition = ProductDefinitionHydrator::hydrate($fixture);
        $this->assertSame($fixture, ProductDefinitionExtractor::extract($definition));
    }

    public function testProductDefinitionValidatorCoversNestedCanonicalFamilies(): void
    {
        $fixture = self::fixture('product-definition.json');
        $mutations = [
            'unknown filter property' => static function (array &$value): void { $value['filters'][0]['flags'] = []; },
            'invalid capability requirement' => static function (array &$value): void { $value['filters'][0]['capabilities']['refill'] = 'yes'; },
            'invalid field variant' => static function (array &$value): void { $value['fields'][0]['variant'] = 1; },
            'invalid field binding' => static function (array &$value): void { $value['fields'][0]['bind_id'] = ['bad' => true]; },
            'non-object defaults' => static function (array &$value): void { $value['fields'][0]['defaults'] = ['a', 'b']; },
            'unknown recursive option key' => static function (array &$value): void {
                $value['fields'][1]['options'][0]['children'] = [['id' => 'child', 'label' => 'Child', 'legacy' => true]];
            },
            'non-primitive option value' => static function (array &$value): void { $value['fields'][1]['options'][0]['value'] = ['bad']; },
            'invalid quantity expression' => static function (array &$value): void {
                $value['fields'][0]['quantity'] = ['value_by' => 'eval', 'expression' => ['language' => 'javascript', 'body' => '', 'callback' => 'x']];
            },
            'invalid validation operator' => static function (array &$value): void { $value['fields'][0]['validation'] = [['op' => 'contains']]; },
            'invalid utility mode' => static function (array &$value): void { $value['fields'][0]['utility'] = ['rate' => 1, 'mode' => 'authoritative']; },
            'invalid option effect key' => static function (array &$value): void { $value['option_effects_for_buttons']['option:premium']['field:notes']['forceVisible'] = true; },
            'invalid value effect mode' => static function (array &$value): void { $value['value_effects_for_triggers']['option:rush']['field:notes']['mode'] = 'sometimes'; },
            'invalid fallback map' => static function (array &$value): void { $value['fallbacks']['nodes'] = ['not-a-map']; },
            'invalid notice kind' => static function (array &$value): void { $value['notices'][0]['kind'] = 'legacy'; },
            'non-object metadata' => static function (array &$value): void { $value['meta'] = ['not', 'an', 'object']; },
        ];

        foreach ($mutations as $label => $mutate) {
            $candidate = $fixture;
            $mutate($candidate);
            $this->assertNotSame([], ProductDefinitionValidator::validate($candidate), $label);
        }
    }

    public function testProductDefinitionRejectsTheRemovedFieldComponentProperty(): void
    {
        $fixture = self::fixture('product-definition-component-property.json');
        $errors = ProductDefinitionValidator::validate($fixture);

        $this->assertArrayHasKey('fields.0.component', $errors);
    }

    public function testProductDefinitionHydrationRejectsDerivedCapabilityFields(): void
    {
        $fixture = self::fixture('product-definition-derived-capabilities.json');
        $this->expectException(InvalidArgumentException::class);

        ProductDefinitionHydrator::hydrate($fixture);
    }

    /** @dataProvider invalidProductDefinitionFixtures */
    public function testProductDefinitionRejectsEveryRatifiedInvalidStructuralFixture(string $fixtureName): void
    {
        $errors = ProductDefinitionValidator::validate(self::fixture($fixtureName));
        $this->assertNotSame([], $errors, "Expected {$fixtureName} to be rejected.");
    }

    /** @return iterable<string, array{string}> */
    public static function invalidProductDefinitionFixtures(): iterable
    {
        yield 'camel-case effect' => ['product-definition-camel-case-effect.json'];
        yield 'missing expression body' => ['product-definition-expression-missing-body.json'];
        yield 'missing schema version' => ['product-definition-missing-version.json'];
        yield 'removed component' => ['product-definition-component-property.json'];
        yield 'derived capabilities' => ['product-definition-derived-capabilities.json'];
    }

    public function testServiceCapabilityRejectsNullMeta(): void
    {
        $fixture = self::fixture('service-capability-null-meta.json');
        $this->expectException(InvalidArgumentException::class);

        Hydrator::hydrate(ServiceCapability::class, $fixture);
    }

    public function testServiceCapabilityRejectsMissingEnabledState(): void
    {
        $fixture = self::fixture('service-capability-missing-enabled.json');
        $this->expectException(InvalidArgumentException::class);

        Hydrator::hydrate(ServiceCapability::class, $fixture);
    }

    public function testHandlerServiceRejectsLegacyWireFields(): void
    {
        $fixture = self::fixture('handler-service-legacy-fields.json');
        $this->expectException(InvalidArgumentException::class);

        Hydrator::hydrate(HandlerService::class, $fixture);
    }

    public function testHandlerServiceRejectsCapabilityKeyMismatch(): void
    {
        $fixture = self::fixture('handler-service-capability-key-mismatch.json');
        $this->expectException(InvalidArgumentException::class);

        Hydrator::hydrate(HandlerService::class, $fixture);
    }

    /** @return array<string, mixed> */
    private static function fixture(string $name): array
    {
        $decoded = json_decode(self::fixtureContents($name), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    private static function fixtureContents(string $name): string
    {
        $contents = file_get_contents(__DIR__ . '/../Fixtures/Contracts/' . $name);
        self::assertIsString($contents);

        return $contents;
    }

    private static function assertCanonicalJson(string $fixtureName, JsonSerializable $binding): void
    {
        $expected = json_decode(self::fixtureContents($fixtureName), false, flags: JSON_THROW_ON_ERROR);
        $actual = json_decode(json_encode($binding, JSON_THROW_ON_ERROR), false, flags: JSON_THROW_ON_ERROR);
        self::assertEquals($expected, $actual);
    }
}
