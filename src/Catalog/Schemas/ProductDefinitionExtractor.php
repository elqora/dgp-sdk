<?php

namespace Elqora\Dgp\Catalog\Schemas;

use Elqora\Dgp\Support\Hydrator;

final class ProductDefinitionExtractor
{
    /**
     * Extract a ProductDefinition DTO into a raw array.
     *
     * @param ProductDefinition $definition
     * @return array<string, mixed>
     */
    public static function extract(ProductDefinition $definition): array
    {
        return Hydrator::serialize($definition);
    }
}
