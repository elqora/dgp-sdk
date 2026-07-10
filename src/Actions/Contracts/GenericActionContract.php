<?php

namespace Elqora\Dgp\Actions\Contracts;

use Elqora\Dgp\Actions\GenericActionRequest;
use Elqora\Dgp\Errors\Result;

interface GenericActionContract
{
    /**
     * Handle an arbitrary handler-defined or host-defined action.
     *
     * @param GenericActionRequest $request
     * @return Result<null>
     */
    public function handleGenericAction(GenericActionRequest $request): Result;
}
