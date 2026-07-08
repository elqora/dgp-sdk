<?php

namespace Elqora\Dgp\Snapshots;

use Elqora\Dgp\Support\Hydrator;

final class OrderSnapshotHydrator
{
    /**
     * Hydrate a raw array into an OrderSnapshot DTO.
     *
     * @param array<string, mixed> $data
     * @return OrderSnapshot
     */
    public static function hydrate(array $data): OrderSnapshot
    {
        return Hydrator::hydrate(OrderSnapshot::class, $data);
    }
}
