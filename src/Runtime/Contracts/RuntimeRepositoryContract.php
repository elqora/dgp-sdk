<?php

namespace Elqora\Dgp\Runtime\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\References\HandlerReference;

interface RuntimeRepositoryContract
{
    /**
     * Resolve a repository permanently scoped to one handler.
     *
     * @param HandlerReference $handler
     * @return Result<HandlerRuntimeRepositoryContract>
     */
    public function forHandler(HandlerReference $handler): Result;
}
