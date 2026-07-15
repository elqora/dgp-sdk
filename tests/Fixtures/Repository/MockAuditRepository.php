<?php

namespace Elqora\Dgp\Tests\Fixtures\Repository;

use Elqora\Dgp\Audits\Contracts\AuditRepositoryContract;
use Elqora\Dgp\Audits\Contracts\HandlerAuditRepositoryContract;
use Elqora\Dgp\Errors\DgpError;
use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\References\HandlerReference;

class MockAuditRepository implements AuditRepositoryContract
{
    /**
     * @var array<string, mixed>
     */
    private array $store;

    /**
     * @param array<string, mixed> $store
     */
    public function __construct(array &$store)
    {
        $this->store = &$store;
        $this->store['audit_records'] ??= [];
    }

    public function forHandler(HandlerReference $handler): Result
    {
        if ($handler->value === 'unknown-handler') {
            /** @var Result<HandlerAuditRepositoryContract> $fail */
            $fail = Result::failure(new DgpError(
                code: 'unknown_handler',
                message: 'Unknown handler reference provided.'
            ));
            return $fail;
        }

        return Result::success(
            new MockHandlerAuditRepository($this->store, $handler)
        );
    }
}
