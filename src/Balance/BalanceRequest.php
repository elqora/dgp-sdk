<?php

declare(strict_types=1);

namespace Elqora\Dgp\Balance;

use Elqora\ConfigKit\Support\ConfigBag;
use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class BalanceRequest implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        public ?ConfigBag $config = null,
        public ?array $meta = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'config' => $this->config ? [
                'sandbox' => $this->config->sandbox,
                'options' => $this->config->options,
                'secrets' => $this->config->secrets,
            ] : null,
            'meta' => $this->meta,
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
