<?php

namespace Elqora\Dgp\Runtime;

final readonly class CancelRequest
{
    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $context
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $orderId,
        public string $handlerKey,
        public array $state = [],
        public array $context = [],
        public array $meta = [],
    ) {}
}
