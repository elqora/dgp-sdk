<?php

namespace Elqora\Dgp\Tests\Fixtures\Repository;

use Elqora\Dgp\Deliveries\Contracts\DeliveriesRepositoryContract;
use Elqora\Dgp\Deliveries\Contracts\HandlerDeliveriesRepositoryContract;
use Elqora\Dgp\Errors\DgpError;
use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\References\HandlerReference;

class MockDeliveriesRepository implements DeliveriesRepositoryContract
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
    }

    public function forHandler(HandlerReference $handler): Result
    {
        if ($handler->value === 'unknown-handler') {
            /** @var Result<HandlerDeliveriesRepositoryContract> $fail */
            $fail = Result::failure(new DgpError(
                code: 'unknown_handler',
                message: 'Unknown handler reference provided.'
            ));
            return $fail;
        }

        return Result::success(
            new MockHandlerDeliveriesRepository($this->store, $handler)
        );
    }
}
