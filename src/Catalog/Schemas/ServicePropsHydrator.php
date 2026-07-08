<?php

namespace Elqora\Dgp\Catalog\Schemas;

use Elqora\Dgp\Support\Hydrator;

final class ServicePropsHydrator
{
    /**
     * Hydrate an array into a ServiceProps DTO.
     *
     * @param array<string, mixed> $data
     * @return ServiceProps
     */
    public static function hydrate(array $data): ServiceProps
    {
        return Hydrator::hydrate(ServiceProps::class, $data);
    }
}
