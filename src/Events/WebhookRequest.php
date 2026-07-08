<?php

namespace Elqora\Dgp\Events;

final readonly class WebhookRequest
{
    /**
     * @param array<string, string|list<string>> $headers
     * @param array<string, mixed> $queryParams
     */
    public function __construct(
        public string $body,
        public array $headers = [],
        public string $method = 'POST',
        public array $queryParams = [],
    ) {}
}
