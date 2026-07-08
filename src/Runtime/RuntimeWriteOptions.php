<?php

namespace Elqora\Dgp\Runtime;

final readonly class RuntimeWriteOptions
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public ?int $expectedRevision = null,
        public ?string $operationKey = null,
        public ?string $updateId = null,
        public ?string $occurredAt = null,
        public array $meta = [],
    ) {}
}
