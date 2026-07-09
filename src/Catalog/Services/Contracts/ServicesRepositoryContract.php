<?php

namespace Elqora\Dgp\Catalog\Services\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\References\HandlerReference;

interface ServicesRepositoryContract
{
    /**
     * Resolve a services repository permanently scoped to one handler.
     *
     * @param HandlerReference $handler
     * @return Result<HandlerServicesRepositoryContract>
     */
    public function forHandler(HandlerReference $handler): Result;
}
