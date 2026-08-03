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

    public function testServiceCapabilityRejectsNullMeta(): void
    {
        $fixture = self::fixture('service-capability-null-meta.json');
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
