<?php

namespace Elqora\Dgp\Catalog\Schemas;

use Elqora\Dgp\Support\Hydrator;

final class ProductDefinitionHydrator
{
    /**
     * Hydrate an array into a ProductDefinition DTO.
     *
     * @param array<string, mixed> $data
     * @return ProductDefinition
     */
    public static function hydrate(array $data): ProductDefinition
    {
        return Hydrator::hydrate(ProductDefinition::class, $data);
    }
}
