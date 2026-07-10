<?php

namespace Elqora\Dgp\Tests\Fixtures\Repository;

use Elqora\Dgp\Runtime\Contracts\RuntimeRepositoryContract;
use Elqora\Dgp\Runtime\Contracts\HandlerRuntimeRepositoryContract;
use Elqora\Dgp\Runtime\References\HandlerReference;
use Elqora\Dgp\Runtime\Plan;
use Elqora\Dgp\Runtime\StartResult;
use Elqora\Dgp\Runtime\RuntimeWriteOptions;
use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Errors\DgpError;
use ReflectionMethod;

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

    /**
     * @return Result<Plan>
     */
    public function seedPlan(
        HandlerReference $handler,
        string|int $orderId,
        Plan $plan,
        ?RuntimeWriteOptions $options = null,
    ): Result {
        /** @var Result<Plan> $result */
        $result = $this->callHostPersistence(
            $handler,
            'persistPlan',
            [$orderId, $plan, $options]
        );

        return $result;
    }

    /**
     * @return Result<StartResult>
     */
    public function seedStartResult(
        HandlerReference $handler,
        string|int $planId,
        StartResult $startResult,
        ?RuntimeWriteOptions $options = null,
    ): Result {
        /** @var Result<StartResult> $result */
        $result = $this->callHostPersistence(
            $handler,
            'persistStartResult',
            [$planId, $startResult, $options]
        );

        return $result;
    }

    /**
     * @param list<\Elqora\Dgp\Deliveries\Delivery> $deliveries
     * @return Result<list<\Elqora\Dgp\Deliveries\Delivery>>
     */
    public function seedDeliveries(
        HandlerReference $handler,
        string|int $orderId,
        array $deliveries,
        ?RuntimeWriteOptions $options = null,
    ): Result {
        /** @var Result<list<\Elqora\Dgp\Deliveries\Delivery>> $result */
        $result = $this->callHostPersistence(
            $handler,
            'persistDeliveries',
            [$orderId, $deliveries, $options]
        );

        return $result;
    }

    public function setCurrentPlan(
        HandlerReference $handler,
        string|int $orderId,
        string|int|null $planId,
    ): void {
        (new MockHandlerRuntimeRepository($this->store, $handler))
            ->setCurrentPlan($orderId, $planId);
    }

    public function setCurrentStartResult(
        HandlerReference $handler,
        string|int $orderId,
        string|int|null $startId,
    ): void {
        (new MockHandlerRuntimeRepository($this->store, $handler))
            ->setCurrentStartResult($orderId, $startId);
    }

    /**
     * @param list<mixed> $arguments
     * @return Result<mixed>
     */
    private function callHostPersistence(
        HandlerReference $handler,
        string $method,
        array $arguments,
    ): Result {
        $repository = new MockHandlerRuntimeRepository($this->store, $handler);
        $reflection = new ReflectionMethod($repository, $method);
        $reflection->setAccessible(true);

        /** @var Result<mixed> $result */
        $result = $reflection->invokeArgs($repository, $arguments);

        return $result;
    }
}
