<?php

namespace Elqora\Dgp\Insights\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\References\HandlerReference;

interface InsightsRepositoryContract
{
    /**
     * Resolve an insights repository permanently scoped to one handler.
     *
     * @param HandlerReference $handler
     * @return Result<HandlerInsightsRepositoryContract>
     */
    public function forHandler(HandlerReference $handler): Result;
}
