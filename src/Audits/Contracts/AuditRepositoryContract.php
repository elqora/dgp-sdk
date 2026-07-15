<?php

namespace Elqora\Dgp\Audits\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\References\HandlerReference;

interface AuditRepositoryContract
{
    /**
     * Resolve an audit repository permanently scoped to one handler.
     *
     * @return Result<HandlerAuditRepositoryContract>
     */
    public function forHandler(
        HandlerReference $handler,
    ): Result;
}
