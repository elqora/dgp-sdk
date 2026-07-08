<?php

namespace Elqora\Dgp\Events\Contracts;

use Elqora\Dgp\Events\DgpEvent;

interface EventHookContract
{
    /**
     * Dispatch a neutral DGP event to the host.
     *
     * @param DgpEvent $event
     * @return void
     */
    public function dispatch(DgpEvent $event): void;
}
