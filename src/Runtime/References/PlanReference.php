<?php

namespace Elqora\Dgp\Runtime\References;

use InvalidArgumentException;

final readonly class PlanReference
{
    public function __construct(
        public string|int|null $id = null,
        public ?string $key = null,
    ) {
        if ($id === null && $key === null) {
            throw new InvalidArgumentException(
                'A plan ID or key is required.'
            );
        }
    }
}
