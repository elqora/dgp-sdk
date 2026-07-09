<?php

namespace Elqora\Dgp\Tests\Fixtures\Repository;

use Elqora\Dgp\Errors\DgpError;
use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Insights\Contracts\HandlerInsightsRepositoryContract;
use Elqora\Dgp\Insights\Contracts\InsightsRepositoryContract;
use Elqora\Dgp\Runtime\References\HandlerReference;

class MockInsightsRepository implements InsightsRepositoryContract
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
            /** @var Result<HandlerInsightsRepositoryContract> $fail */
            $fail = Result::failure(new DgpError(
                code: 'unknown_handler',
                message: 'Unknown handler reference provided.'
            ));
            return $fail;
        }

        return Result::success(
            new MockHandlerInsightsRepository($this->store, $handler)
        );
    }
}
