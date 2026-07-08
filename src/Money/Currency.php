<?php

namespace Elqora\Dgp\Money;

use InvalidArgumentException;

final readonly class Currency
{
    public string $code;

    public function __construct(string $code)
    {
        $code = strtoupper(trim($code));

        if (!preg_match('/^[A-Z]{3}$/', $code)) {
            throw new InvalidArgumentException(
                'Currency must be a three-letter uppercase code.'
            );
        }

        $this->code = $code;
    }
}
