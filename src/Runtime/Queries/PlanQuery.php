<?php

namespace Elqora\Dgp\Runtime\Queries;

final readonly class PlanQuery
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public ?string $key = null,
        public ?bool $active = null,
        public ?string $status = null,
        public ?int $limit = null,
        public ?string $cursor = null,
        public array $meta = [],
    ) {}
}
