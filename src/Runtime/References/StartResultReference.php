<?php

namespace Elqora\Dgp\Runtime\References;

use InvalidArgumentException;

final readonly class StartResultReference
{
    public function __construct(
        public string|int|null $id = null,
        public ?string $key = null,
    ) {
        if ($id === null && $key === null) {
            throw new InvalidArgumentException(
                'A start-result ID or key is required.'
            );
        }
    }
}
