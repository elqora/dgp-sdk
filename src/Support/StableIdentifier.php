<?php

namespace Elqora\Dgp\Support;

use InvalidArgumentException;

final class StableIdentifier
{
    public static function isStable(string $value): bool
    {
        return preg_match('/^[A-Za-z0-9][A-Za-z0-9_.:-]*$/', $value) === 1;
    }

    public static function assert(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("{$field} must not be empty.");
        }

        if (! self::isStable($value)) {
            throw new InvalidArgumentException("{$field} must be a stable identifier.");
        }
    }

    /**
     * @param list<string> $keys
     */
    public static function assertUnique(array $keys, string $field): void
    {
        $seen = [];

        foreach ($keys as $key) {
            self::assert($key, $field);

            if (isset($seen[$key])) {
                throw new InvalidArgumentException("{$field} values must be unique.");
            }

            $seen[$key] = true;
        }
    }
}
