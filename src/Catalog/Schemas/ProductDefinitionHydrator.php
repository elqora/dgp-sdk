<?php

namespace Elqora\Dgp\Catalog\Schemas;

use Elqora\Dgp\Support\Hydrator;
use InvalidArgumentException;

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
        $errors = ProductDefinitionValidator::validate($data);
        if ($errors !== []) {
            throw new InvalidArgumentException('Invalid canonical ProductDefinition: ' . json_encode($errors, JSON_THROW_ON_ERROR));
        }

        return Hydrator::hydrate(ProductDefinition::class, $data);
    }
}
