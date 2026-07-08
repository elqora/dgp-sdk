<?php

namespace Elqora\Dgp\Catalog\Schemas;

use Elqora\Dgp\Support\Hydrator;

final class ServicePropsExtractor
{
    /**
     * Extract a ServiceProps DTO into a raw array.
     *
     * @param ServiceProps $props
     * @return array<string, mixed>
     */
    public static function extract(ServiceProps $props): array
    {
        return Hydrator::serialize($props);
    }
}
