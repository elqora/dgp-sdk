<?php

namespace Elqora\Dgp\Assets;

final readonly class ResolvePrivateAssetRequest
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $orderId,
        public string $assetKey,
        public array $meta = [],
    ) {}
}
