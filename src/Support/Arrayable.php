<?php

namespace Elqora\Dgp\Support;

interface Arrayable
{
    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
