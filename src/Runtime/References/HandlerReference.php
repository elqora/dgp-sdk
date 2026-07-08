<?php

namespace Elqora\Dgp\Runtime\References;

final readonly class HandlerReference
{
    private function __construct(
        public string|int $value,
        public HandlerReferenceType $type,
    ) {}

    public static function fromId(string|int $id): self
    {
        return new self($id, HandlerReferenceType::ID);
    }

    public static function fromKey(string $key): self
    {
        return new self($key, HandlerReferenceType::KEY);
    }

    public static function fromAlias(string $alias): self
    {
        return new self($alias, HandlerReferenceType::ALIAS);
    }
}
