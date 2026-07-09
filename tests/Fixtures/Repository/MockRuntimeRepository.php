<?php

namespace Elqora\Dgp\Tests\Fixtures\Repository;

use Elqora\Dgp\Runtime\Contracts\RuntimeRepositoryContract;
use Elqora\Dgp\Runtime\Contracts\HandlerRuntimeRepositoryContract;
use Elqora\Dgp\Runtime\References\HandlerReference;
use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Errors\DgpError;

class MockRuntimeRepository implements RuntimeRepositoryContract
{
    /**
     * Shared in-memory data store reference.
     *
     * @var array<string, mixed>
     */
    public array $store = [
        'plans' => [],          // Keyed by order_id
        'start_results' => [],  // Keyed by order_id
        'deliveries' => [],     // Keyed by order_id
        'services' => [],       // Keyed by handler and service ID
        'current_plan' => [],   // Keyed by order_id
        'current_start' => [],  // Keyed by order_id
        'idempotency' => [],    // Keyed by unique cache key
        'service_states' => [], // Keyed by handler and service ID
        'analyses' => [],       // Keyed by handler
        'scoreboards' => [],    // Keyed by handler
        'leaderboards' => [],   // Keyed by handler
    ];

    public function forHandler(HandlerReference $handler): Result
    {
        if ($handler->value === 'unknown-handler') {
            /** @var Result<HandlerRuntimeRepositoryContract> $fail */
            $fail = Result::failure(new DgpError(
                code: 'unknown_handler',
                message: 'Unknown handler reference provided.'
            ));
            return $fail;
        }

        return Result::success(
            new MockHandlerRuntimeRepository($this->store, $handler)
        );
    }
}
