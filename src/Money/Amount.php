<?php

namespace Elqora\Dgp\Money;

use InvalidArgumentException;

final readonly class Amount
{
    public function __construct(public string $value)
    {
        if (!preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
            throw new InvalidArgumentException(
                'Amount must be a decimal string.'
            );
        }
    }
}
