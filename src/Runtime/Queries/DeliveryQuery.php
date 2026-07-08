<?php

namespace Elqora\Dgp\Runtime\Queries;

use Elqora\Dgp\Deliveries\DeliveryStatus;

final readonly class DeliveryQuery
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public ?DeliveryStatus $status = null,
        public ?bool $active = null,
        public ?int $limit = null,
        public ?string $cursor = null,
        public array $meta = [],
    ) {}
}
