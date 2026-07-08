<?php

namespace Elqora\Dgp\Errors;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;
use RuntimeException;

/**
 * @template-covariant TValue
 */
final readonly class Result implements Arrayable, JsonSerializable
{
     /**
      * @param bool $success
      * @param TValue $value
      * @param DgpError|null $error
      */
     private function __construct(
         private bool $success,
         private mixed $value,
         private ?DgpError $error
     ) {}

    /**
     * Create a successful result.
     *
     * @template T
     * @param T $value
     * @return self<T>
     */
    public static function success(mixed $value): self
    {
        return new self(true, $value, null);
    }

    /**
     * Create a failed result.
     *
     * @return self<null>
     */
    public static function failure(DgpError $error): self
    {
        return new self(false, null, $error);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Get the success value.
     *
     * @return TValue
     * @throws RuntimeException If the result is a failure.
     */
    public function value(): mixed
    {
        if (!$this->success) {
            throw new RuntimeException('Cannot retrieve value from a failed Result: ' . ($this->error ? $this->error->message : 'Unknown error'));
        }
        return $this->value;
    }

    public function error(): ?DgpError
    {
        return $this->error;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'value' => $this->value instanceof Arrayable ? $this->value->toArray() : $this->value,
            'error' => $this->error?->toArray(),
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
