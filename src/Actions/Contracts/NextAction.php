<?php

namespace Elqora\Dgp\Actions\Contracts;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

interface NextAction extends Arrayable, JsonSerializable
{
    /**
     * Get the next action type/discriminator.
     *
     * @return string
     */
    public function type(): string;
}
