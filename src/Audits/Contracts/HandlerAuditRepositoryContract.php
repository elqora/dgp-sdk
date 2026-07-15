<?php

namespace Elqora\Dgp\Audits\Contracts;

use Elqora\Dgp\Audits\AuditQuery;
use Elqora\Dgp\Audits\AuditRecord;
use Elqora\Dgp\Errors\Result;

interface HandlerAuditRepositoryContract
{
    /**
     * Persist one audit record.
     *
     * @return Result<AuditRecord>
     */
    public function record(
        AuditRecord $record,
    ): Result;

    /**
     * Resolve handler audit records.
     *
     * @return Result<list<AuditRecord>>
     */
    public function records(
        ?AuditQuery $query = null,
    ): Result;

    /**
     * Resolve audit records associated with an order.
     *
     * @return Result<list<AuditRecord>>
     */
    public function recordsForOrder(
        string|int $orderId,
        ?AuditQuery $query = null,
    ): Result;
}
