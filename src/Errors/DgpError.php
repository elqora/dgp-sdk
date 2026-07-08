<?php

namespace Elqora\Dgp\Errors;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class DgpError implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $details
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $code,
        public string $message,
        public ?string $providerCode = null,
        public ?int $httpStatus = null,
        public ?int $retryDelay = null,
        public array $details = [],
        public array $metadata = [],
        public bool $retryable = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'provider_code' => $this->providerCode,
            'http_status' => $this->httpStatus,
            'retry_delay' => $this->retryDelay,
            'details' => $this->details,
            'metadata' => $this->metadata,
            'retryable' => $this->retryable,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
