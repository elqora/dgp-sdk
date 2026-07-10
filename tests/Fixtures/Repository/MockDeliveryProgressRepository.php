<?php

namespace Elqora\Dgp\Tests\Fixtures\Repository;

use Elqora\Dgp\Errors\DgpError;
use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Progress\Contracts\DeliveryProgressRepositoryContract;
use Elqora\Dgp\Progress\Contracts\HandlerDeliveryProgressRepositoryContract;
use Elqora\Dgp\Runtime\References\HandlerReference;

class MockDeliveryProgressRepository implements DeliveryProgressRepositoryContract
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
        $this->store['progress_records'] ??= [];
    }

    public function forHandler(HandlerReference $handler): Result
    {
        if ($handler->value === 'unknown-handler') {
            /** @var Result<HandlerDeliveryProgressRepositoryContract> $fail */
            $fail = Result::failure(new DgpError(
                code: 'unknown_handler',
                message: 'Unknown handler reference provided.'
            ));
            return $fail;
        }

        return Result::success(
            new MockHandlerDeliveryProgressRepository($this->store, $handler)
        );
    }
}
