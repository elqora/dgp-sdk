<?php

namespace Elqora\Dgp\Tests\Fixtures\Repository;

use Elqora\Dgp\Runtime\Contracts\HandlerRuntimeRepositoryContract;
use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Errors\DgpError;
use Elqora\Dgp\Runtime\Plan;
use Elqora\Dgp\Runtime\StartResult;
use Elqora\Dgp\Runtime\References\HandlerReference;
use Elqora\Dgp\Runtime\References\PlanReference;
use Elqora\Dgp\Runtime\References\StartResultReference;
use Elqora\Dgp\Runtime\Queries\PlanQuery;
use Elqora\Dgp\Runtime\Queries\StartResultQuery;
use Elqora\Dgp\Runtime\Queries\DeliveryQuery;
use Elqora\Dgp\Runtime\RuntimeWriteOptions;
use Elqora\Dgp\Runtime\OrderRuntimeView;
use Elqora\Dgp\Deliveries\Delivery;
use Elqora\Dgp\Deliveries\InitializationDelivery;
use Elqora\Dgp\Deliveries\FulfillmentDelivery;
use Elqora\Dgp\Deliveries\DeliveryStatus;

class MockHandlerRuntimeRepository implements HandlerRuntimeRepositoryContract
{
    /**
     * @var array<string, mixed>
     */
    private array $store;
    private HandlerReference $handler;
    private static int $autoIncrement = 1000;

    /**
     * @param array<string, mixed> $store
     * @param HandlerReference $handler
     */
    public function __construct(array &$store, HandlerReference $handler)
    {
        $this->store = &$store;
        $this->handler = $handler;
    }

    private function getHandlerValue(): string|int
    {
        return $this->handler->value;
    }

    private function nextId(): int
    {
        return self::$autoIncrement++;
    }

    /**
     * @param string|int $orderId
     * @param string $operation
     * @param RuntimeWriteOptions|null $options
     * @return Result<mixed>|null
     */
    private function checkIdempotency(string|int $orderId, string $operation, ?RuntimeWriteOptions $options): ?Result
    {
        if ($options === null) {
            return null;
        }

        $key = $options->operationKey ?? $options->updateId;
        if ($key === null) {
            return null;
        }

        $cacheKey = $this->getHandlerValue() . ':' . $orderId . ':' . $operation . ':' . $key;
        if (isset($this->store['idempotency'][$cacheKey])) {
            /** @var Result<mixed> $cached */
            $cached = $this->store['idempotency'][$cacheKey];
            return $cached;
        }

        return null;
    }

    /**
     * @param string|int $orderId
     * @param string $operation
     * @param RuntimeWriteOptions|null $options
     * @param Result<mixed> $result
     */
    private function saveIdempotency(string|int $orderId, string $operation, ?RuntimeWriteOptions $options, Result $result): void
    {
        if ($options === null || $result->isFailure()) {
            return;
        }

        $key = $options->operationKey ?? $options->updateId;
        if ($key === null) {
            return;
        }

        $cacheKey = $this->getHandlerValue() . ':' . $orderId . ':' . $operation . ':' . $key;
        $this->store['idempotency'][$cacheKey] = $result;
    }

    /**
     * @return Result<Plan>
     * @phpstan-ignore-next-line invoked through MockRuntimeRepository host-side seeding
     */
    private function persistPlan(string|int $orderId, Plan $plan, ?RuntimeWriteOptions $options = null): Result
    {
        if ($options !== null) {
            $idempotent = $this->checkIdempotency($orderId, 'persistPlan', $options);
            if ($idempotent !== null) {
                /** @var Result<Plan> $res */
                $res = $idempotent;
                return $res;
            }
        }

        // Find existing plan
        $existingIndex = null;
        $actualRevision = 0;
        $existingId = $plan->id;

        foreach ($this->store['plans'] as $idx => $item) {
            if ($item['order_id'] == $orderId && $item['handler'] == $this->getHandlerValue()) {
                if ($plan->id !== null && $item['id'] == $plan->id) {
                    $existingIndex = $idx;
                    $actualRevision = $item['plan']->revision;
                    break;
                } elseif ($plan->id === null && $item['key'] === $plan->key) {
                    $existingIndex = $idx;
                    $existingId = $item['id'];
                    $actualRevision = $item['plan']->revision;
                    break;
                }
            }
        }

        // Check revision conflict
        if ($options !== null && $options->expectedRevision !== null) {
            if ($options->expectedRevision !== $actualRevision) {
                /** @var Result<Plan> $fail */
                $fail = Result::failure(new DgpError(
                    code: 'runtime_revision_conflict',
                    message: "Plan revision conflict. Expected {$options->expectedRevision}, got {$actualRevision}."
                ));
                return $fail;
            }
        }

        // Reconstruct plan with new ID and revision
        /** @var string|int $planId */
        $planId = $existingId ?? $this->nextId();
        $nextRevision = $actualRevision + 1;

        // Converged save of nested deliveries
        $persistedDeliveries = [];
        foreach ($plan->deliveries as $del) {
            $deliveryId = $del->id;
            // Find existing delivery
            foreach ($this->store['deliveries'] as $dItem) {
                if ($dItem['order_id'] == $orderId && $dItem['handler'] == $this->getHandlerValue()) {
                    if ($del->id !== null && $dItem['id'] == $del->id) {
                        $deliveryId = $dItem['id'];
                        break;
                    } elseif ($del->id === null && $dItem['key'] === $del->key && $dItem['parent_id'] == $planId) {
                        $deliveryId = $dItem['id'];
                        break;
                    }
                }
            }
            if ($deliveryId === null) {
                $deliveryId = $this->nextId();
            }

            $persistedDel = new InitializationDelivery(
                id: $deliveryId,
                key: $del->key,
                status: $del->status,
                label: $del->label,
                progress: $del->progress,
                planId: $planId,
                nextAction: $del->nextAction,
                meta: $del->meta,
                kind: $del->kind,
                name: $del->name,
                isPublic: $del->isPublic,
                note: $del->note,
            );

            // Save to store
            $this->storeDelivery($orderId, $planId, $persistedDel);
            $persistedDeliveries[] = $persistedDel;
        }

        $persistedPlan = new Plan(
            id: $planId,
            key: $plan->key,
            state: $plan->state,
            deliveries: $persistedDeliveries,
            nextAction: $plan->nextAction,
            meta: $plan->meta,
            revision: $nextRevision,
            orderId: $orderId,
        );

        $planRecord = [
            'id' => $planId,
            'key' => $plan->key,
            'order_id' => $orderId,
            'handler' => $this->getHandlerValue(),
            'plan' => $persistedPlan
        ];

        if ($existingIndex !== null) {
            $this->store['plans'][$existingIndex] = $planRecord;
        } else {
            $this->store['plans'][] = $planRecord;
        }

        $result = Result::success($persistedPlan);
        $this->saveIdempotency($orderId, 'persistPlan', $options, $result);
        return $result;
    }

    public function findPlan(string|int $orderId, PlanReference $reference): Result
    {
        foreach ($this->store['plans'] as $item) {
            if ($item['order_id'] == $orderId && $item['handler'] == $this->getHandlerValue()) {
                if ($reference->id !== null && $item['id'] == $reference->id) {
                    return Result::success($item['plan']);
                }
                if ($reference->id === null && $reference->key !== null && $item['key'] === $reference->key) {
                    return Result::success($item['plan']);
                }
            }
        }
        /** @var Plan|null $empty */
        $empty = null;
        return Result::success($empty);
    }

    public function plans(string|int $orderId, ?PlanQuery $query = null): Result
    {
        /** @var list<Plan> $results */
        $results = [];
        foreach ($this->store['plans'] as $item) {
            if ($item['order_id'] == $orderId && $item['handler'] == $this->getHandlerValue()) {
                if ($query !== null) {
                    if ($query->key !== null && $item['key'] !== $query->key) {
                        continue;
                    }
                }
                /** @var Plan $p */
                $p = $item['plan'];
                $results[] = $p;
            }
        }
        return Result::success($results);
    }

    /**
     * @return Result<StartResult>
     * @phpstan-ignore-next-line invoked through MockRuntimeRepository host-side seeding
     */
    private function persistStartResult(string|int $planId, StartResult $startResult, ?RuntimeWriteOptions $options = null): Result
    {
        // Resolve parent Plan by planId
        $parentPlan = null;
        $orderId = null;
        foreach ($this->store['plans'] as $item) {
            if ($item['handler'] == $this->getHandlerValue() && $item['id'] == $planId) {
                $parentPlan = $item['plan'];
                $orderId = $item['order_id'];
                break;
            }
        }

        if ($parentPlan === null || $orderId === null) {
            /** @var Result<StartResult> $fail */
            $fail = Result::failure(new DgpError(
                code: 'parent_plan_not_found',
                message: 'The referenced parent plan does not exist or does not belong to the current handler.'
            ));
            return $fail;
        }

        if ($options !== null) {
            $idempotent = $this->checkIdempotency($orderId, 'persistStartResult', $options);
            if ($idempotent !== null) {
                /** @var Result<StartResult> $res */
                $res = $idempotent;
                return $res;
            }
        }

        // Validation:
        // If StartResult.planId is present, it must equal the supplied planId
        if ($startResult->planId !== null && $startResult->planId != $planId) {
            /** @var Result<StartResult> $fail */
            $fail = Result::failure(new DgpError(
                code: 'parent_plan_reference_mismatch',
                message: 'The StartResult planId does not match the supplied planId.'
            ));
            return $fail;
        }

        // If StartResult.planKey is present, it must equal the resolved persisted plan's key
        if ($startResult->planKey !== null && $startResult->planKey !== $parentPlan->key) {
            /** @var Result<StartResult> $fail */
            $fail = Result::failure(new DgpError(
                code: 'parent_plan_reference_mismatch',
                message: 'The StartResult planKey does not match the resolved plan key.'
            ));
            return $fail;
        }

        // Find existing start result
        $existingIndex = null;
        $actualRevision = 0;
        $existingId = $startResult->id;

        foreach ($this->store['start_results'] as $idx => $item) {
            if ($item['order_id'] == $orderId && $item['handler'] == $this->getHandlerValue()) {
                if ($startResult->id !== null && $item['id'] == $startResult->id) {
                    $existingIndex = $idx;
                    $actualRevision = $item['start_result']->revision;
                    break;
                } elseif ($startResult->id === null && $item['key'] === $startResult->key) {
                    $existingIndex = $idx;
                    $existingId = $item['id'];
                    $actualRevision = $item['start_result']->revision;
                    break;
                }
            }
        }

        // Check revision conflict
        if ($options !== null && $options->expectedRevision !== null) {
            if ($options->expectedRevision !== $actualRevision) {
                /** @var Result<StartResult> $fail */
                $fail = Result::failure(new DgpError(
                    code: 'runtime_revision_conflict',
                    message: "StartResult revision conflict. Expected {$options->expectedRevision}, got {$actualRevision}."
                ));
                return $fail;
            }
        }

        /** @var string|int $startId */
        $startId = $existingId ?? $this->nextId();
        $nextRevision = $actualRevision + 1;

        // Persist nested fulfillment deliveries
        $persistedDeliveries = [];
        foreach ($startResult->deliveries as $del) {
            $deliveryId = $del->id;
            foreach ($this->store['deliveries'] as $dItem) {
                if ($dItem['order_id'] == $orderId && $dItem['handler'] == $this->getHandlerValue()) {
                    if ($del->id !== null && $dItem['id'] == $del->id) {
                        $deliveryId = $dItem['id'];
                        break;
                    } elseif ($del->id === null && $dItem['key'] === $del->key && $dItem['parent_id'] == $startId) {
                        $deliveryId = $dItem['id'];
                        break;
                    }
                }
            }
            if ($deliveryId === null) {
                $deliveryId = $this->nextId();
            }

            $persistedDel = new FulfillmentDelivery(
                id: $deliveryId,
                key: $del->key,
                status: $del->status,
                label: $del->label,
                progress: $del->progress,
                startId: $startId,
                nextAction: $del->nextAction,
                meta: $del->meta,
                kind: $del->kind,
                name: $del->name,
                isPublic: $del->isPublic,
                note: $del->note,
            );

            $this->storeDelivery($orderId, $startId, $persistedDel);
            $persistedDeliveries[] = $persistedDel;
        }

        $persistedStart = new StartResult(
            id: $startId,
            key: $startResult->key,
            state: $startResult->state,
            deliveries: $persistedDeliveries,
            nextAction: $startResult->nextAction,
            meta: $startResult->meta,
            planId: $planId,
            planKey: $parentPlan->key,
            revision: $nextRevision
        );

        $startRecord = [
            'id' => $startId,
            'key' => $startResult->key,
            'order_id' => $orderId,
            'handler' => $this->getHandlerValue(),
            'start_result' => $persistedStart
        ];

        if ($existingIndex !== null) {
            $this->store['start_results'][$existingIndex] = $startRecord;
        } else {
            $this->store['start_results'][] = $startRecord;
        }

        $result = Result::success($persistedStart);
        $this->saveIdempotency($orderId, 'persistStartResult', $options, $result);
        return $result;
    }

    public function findStartResult(string|int $orderId, StartResultReference $reference): Result
    {
        foreach ($this->store['start_results'] as $item) {
            if ($item['order_id'] == $orderId && $item['handler'] == $this->getHandlerValue()) {
                if ($reference->id !== null && $item['id'] == $reference->id) {
                    return Result::success($item['start_result']);
                }
                if ($reference->id === null && $reference->key !== null && $item['key'] === $reference->key) {
                    return Result::success($item['start_result']);
                }
            }
        }
        /** @var StartResult|null $empty */
        $empty = null;
        return Result::success($empty);
    }

    public function startResults(string|int $orderId, ?StartResultQuery $query = null): Result
    {
        /** @var list<StartResult> $results */
        $results = [];
        foreach ($this->store['start_results'] as $item) {
            if ($item['order_id'] == $orderId && $item['handler'] == $this->getHandlerValue()) {
                if ($query !== null) {
                    if ($query->key !== null && $item['key'] !== $query->key) {
                        continue;
                    }
                    if ($query->planId !== null && $item['start_result']->planId != $query->planId) {
                        continue;
                    }
                }
                /** @var StartResult $s */
                $s = $item['start_result'];
                $results[] = $s;
            }
        }
        return Result::success($results);
    }

    public function deliveriesForPlan(string|int $orderId, PlanReference $plan, ?DeliveryQuery $query = null): Result
    {
        // Resolve the plan first to verify access and get plan ID
        $planRes = $this->findPlan($orderId, $plan);
        if ($planRes->isFailure() || $planRes->value() === null) {
            /** @var list<InitializationDelivery> $empty */
            $empty = [];
            return Result::success($empty);
        }
        $resolvedPlan = $planRes->value();

        $results = [];
        foreach ($this->store['deliveries'] as $item) {
            if ($item['order_id'] == $orderId && $item['handler'] == $this->getHandlerValue()) {
                if ($item['parent_id'] == $resolvedPlan->id && $item['delivery'] instanceof InitializationDelivery) {
                    $results[] = $item['delivery'];
                }
            }
        }
        return Result::success($results);
    }

    public function deliveriesForStartResult(string|int $orderId, StartResultReference $startResult, ?DeliveryQuery $query = null): Result
    {
        $startRes = $this->findStartResult($orderId, $startResult);
        if ($startRes->isFailure() || $startRes->value() === null) {
            /** @var list<FulfillmentDelivery> $empty */
            $empty = [];
            return Result::success($empty);
        }
        $resolvedStart = $startRes->value();

        $results = [];
        foreach ($this->store['deliveries'] as $item) {
            if ($item['order_id'] == $orderId && $item['handler'] == $this->getHandlerValue()) {
                if ($item['parent_id'] == $resolvedStart->id && $item['delivery'] instanceof FulfillmentDelivery) {
                    $results[] = $item['delivery'];
                }
            }
        }
        return Result::success($results);
    }

    /**
     * @param list<Delivery> $deliveries
     * @return Result<list<Delivery>>
     * @phpstan-ignore-next-line invoked through MockRuntimeRepository host-side seeding
     */
    private function persistDeliveries(string|int $orderId, array $deliveries, ?RuntimeWriteOptions $options = null): Result
    {
        if ($options !== null && $options->expectedRevision !== null) {
            /** @var Result<array<int, Delivery>> $fail */
            $fail = Result::failure(new DgpError(
                code: 'delivery_revision_not_supported',
                message: 'Delivery batch writes do not support expectedRevision.'
            ));
            return $fail;
        }

        if ($options !== null) {
            $idempotent = $this->checkIdempotency($orderId, 'persistDeliveries', $options);
            if ($idempotent !== null) {
                /** @var Result<array<int, Delivery>> $res */
                $res = $idempotent;
                return $res;
            }
        }

        $persisted = [];

        foreach ($deliveries as $del) {
            // Determine parent identity and type
            $parentId = $del->planId ?? $del->startId;
            if ($parentId === null) {
                /** @var Result<array<int, Delivery>> $fail */
                $fail = Result::failure(new DgpError(
                    code: 'missing_parent_reference',
                    message: 'Deliveries must have a planId or startId parent reference.'
                ));
                return $fail;
            }

            // Verify parent ownership and correct matching type
            $parentExists = false;
            if ($del instanceof InitializationDelivery) {
                foreach ($this->store['plans'] as $item) {
                    if ($item['order_id'] == $orderId && $item['handler'] == $this->getHandlerValue() && $item['id'] == $parentId) {
                        $parentExists = true;
                        break;
                    }
                }
            } elseif ($del instanceof FulfillmentDelivery) {
                foreach ($this->store['start_results'] as $item) {
                    if ($item['order_id'] == $orderId && $item['handler'] == $this->getHandlerValue() && $item['id'] == $parentId) {
                        $parentExists = true;
                        break;
                    }
                }
            }

            if (!$parentExists) {
                /** @var Result<array<int, Delivery>> $fail */
                $fail = Result::failure(new DgpError(
                    code: 'parent_not_found',
                    message: 'The referenced parent plan or start result does not exist or does not belong to the scoped handler/order.'
                ));
                return $fail;
            }

            // Find existing delivery
            $existingIndex = null;
            $existingId = $del->id;
            foreach ($this->store['deliveries'] as $idx => $dItem) {
                if ($dItem['order_id'] == $orderId && $dItem['handler'] == $this->getHandlerValue()) {
                    if ($del->id !== null && $dItem['id'] == $del->id) {
                        $existingIndex = $idx;
                        break;
                    } elseif ($del->id === null && $dItem['key'] === $del->key && $dItem['parent_id'] == $parentId) {
                        $existingIndex = $idx;
                        $existingId = $dItem['id'];
                        break;
                    }
                }
            }

            $deliveryId = $existingId ?? $this->nextId();

            if ($del instanceof InitializationDelivery) {
                $persistedDel = new InitializationDelivery(
                    id: $deliveryId,
                    key: $del->key,
                    status: $del->status,
                    label: $del->label,
                    progress: $del->progress,
                    planId: $parentId,
                    nextAction: $del->nextAction,
                    meta: $del->meta,
                    kind: $del->kind,
                    name: $del->name,
                    isPublic: $del->isPublic,
                    note: $del->note,
                );
            } else {
                $persistedDel = new FulfillmentDelivery(
                    id: $deliveryId,
                    key: $del->key,
                    status: $del->status,
                    label: $del->label,
                    progress: $del->progress,
                    startId: $parentId,
                    nextAction: $del->nextAction,
                    meta: $del->meta,
                    kind: $del->kind,
                    name: $del->name,
                    isPublic: $del->isPublic,
                    note: $del->note,
                );
            }

            $this->storeDelivery($orderId, $parentId, $persistedDel);
            $persisted[] = $persistedDel;
        }

        $result = Result::success($persisted);
        $this->saveIdempotency($orderId, 'persistDeliveries', $options, $result);
        return $result;
    }

    public function runtime(string|int $orderId): Result
    {
        $plans = [];
        foreach ($this->store['plans'] as $item) {
            if ($item['order_id'] == $orderId && $item['handler'] == $this->getHandlerValue()) {
                $plans[] = $item['plan'];
            }
        }

        $startResults = [];
        foreach ($this->store['start_results'] as $item) {
            if ($item['order_id'] == $orderId && $item['handler'] == $this->getHandlerValue()) {
                $startResults[] = $item['start_result'];
            }
        }

        $currentPlanId = $this->store['current_plan'][$orderId] ?? null;
        $currentStartId = $this->store['current_start'][$orderId] ?? null;

        $currentPlan = null;
        foreach ($plans as $p) {
            if ($p->id == $currentPlanId) {
                $currentPlan = $p;
                break;
            }
        }

        $currentStartResult = null;
        foreach ($startResults as $s) {
            if ($s->id == $currentStartId) {
                $currentStartResult = $s;
                break;
            }
        }

        return Result::success(new OrderRuntimeView(
            orderId: $orderId,
            plans: $plans,
            startResults: $startResults,
            currentPlan: $currentPlan,
            currentStartResult: $currentStartResult
        ));
    }

    // Helper methods to mutate current selection for test fixtures
    public function setCurrentPlan(string|int $orderId, string|int|null $planId): void
    {
        $this->store['current_plan'][$orderId] = $planId;
    }

    public function setCurrentStartResult(string|int $orderId, string|int|null $startId): void
    {
        $this->store['current_start'][$orderId] = $startId;
    }

    private function storeDelivery(string|int $orderId, string|int $parentId, Delivery $delivery): void
    {
        $existingIndex = null;
        foreach ($this->store['deliveries'] as $idx => $item) {
            if ($item['order_id'] == $orderId && $item['handler'] == $this->getHandlerValue()) {
                if ($item['id'] == $delivery->id) {
                    $existingIndex = $idx;
                    break;
                }
            }
        }

        $record = [
            'id' => $delivery->id,
            'key' => $delivery->key,
            'order_id' => $orderId,
            'handler' => $this->getHandlerValue(),
            'parent_id' => $parentId,
            'delivery' => $delivery
        ];

        if ($existingIndex !== null) {
            $this->store['deliveries'][$existingIndex] = $record;
        } else {
            $this->store['deliveries'][] = $record;
        }
    }
}
