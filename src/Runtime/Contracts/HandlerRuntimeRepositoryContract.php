<?php

namespace Elqora\Dgp\Runtime\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\Plan;
use Elqora\Dgp\Runtime\StartResult;
use Elqora\Dgp\Runtime\References\PlanReference;
use Elqora\Dgp\Runtime\References\StartResultReference;
use Elqora\Dgp\Runtime\Queries\PlanQuery;
use Elqora\Dgp\Runtime\Queries\StartResultQuery;
use Elqora\Dgp\Runtime\Queries\DeliveryQuery;
use Elqora\Dgp\Runtime\OrderRuntimeView;

interface HandlerRuntimeRepositoryContract
{
    /**
     * @param string|int $orderId
     * @param PlanReference $reference
     * @return Result<Plan|null>
     */
    public function findPlan(
        string|int $orderId,
        PlanReference $reference,
    ): Result;

    /**
     * @param string|int $orderId
     * @param PlanQuery|null $query
     * @return Result<list<Plan>>
     */
    public function plans(
        string|int $orderId,
        ?PlanQuery $query = null,
    ): Result;

    /**
     * @param string|int $orderId
     * @param StartResultReference $reference
     * @return Result<StartResult|null>
     */
    public function findStartResult(
        string|int $orderId,
        StartResultReference $reference,
    ): Result;

    /**
     * @param string|int $orderId
     * @param StartResultQuery|null $query
     * @return Result<list<StartResult>>
     */
    public function startResults(
        string|int $orderId,
        ?StartResultQuery $query = null,
    ): Result;

    /**
     * @param string|int $orderId
     * @param PlanReference $plan
     * @param DeliveryQuery|null $query
     * @return Result<list<\Elqora\Dgp\Deliveries\InitializationDelivery>>
     */
    public function deliveriesForPlan(
        string|int $orderId,
        PlanReference $plan,
        ?DeliveryQuery $query = null,
    ): Result;

    /**
     * @param string|int $orderId
     * @param StartResultReference $startResult
     * @param DeliveryQuery|null $query
     * @return Result<list<\Elqora\Dgp\Deliveries\FulfillmentDelivery>>
     */
    public function deliveriesForStartResult(
        string|int $orderId,
        StartResultReference $startResult,
        ?DeliveryQuery $query = null,
    ): Result;

    /**
     * @param string|int $orderId
     * @return Result<OrderRuntimeView>
     */
    public function runtime(
        string|int $orderId,
    ): Result;
}
