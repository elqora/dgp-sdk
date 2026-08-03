<?php

namespace Elqora\Dgp\Snapshots;

use Elqora\Dgp\Support\Hydrator;
use InvalidArgumentException;

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
        $errors = OrderSnapshotValidator::validate($data);
        if ($errors !== []) {
            throw new InvalidArgumentException('OrderSnapshot does not conform to the DGP v1 wire contract: '.json_encode($errors, JSON_THROW_ON_ERROR));
        }

        return Hydrator::hydrate(OrderSnapshot::class, $data);
    }
}
