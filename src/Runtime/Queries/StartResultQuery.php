<?php

namespace Elqora\Dgp\Runtime\Queries;

final readonly class StartResultQuery
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int|null $planId = null,
        public ?string $planKey = null,
        public ?string $key = null,
        public ?bool $active = null,
        public ?string $status = null,
        public ?int $limit = null,
        public ?string $cursor = null,
        public array $meta = [],
    ) {}
}
