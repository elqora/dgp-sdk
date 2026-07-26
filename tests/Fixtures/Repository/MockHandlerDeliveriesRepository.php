<?php

namespace Elqora\Dgp\Tests\Fixtures\Repository;

use Elqora\Dgp\Deliveries\Contracts\HandlerDeliveriesRepositoryContract;
use Elqora\Dgp\Deliveries\Delivery;
use Elqora\Dgp\Deliveries\DeliveryProgress;
use Elqora\Dgp\Deliveries\DeliveryProgressSegment;
use Elqora\Dgp\Deliveries\DeliveryStatus;
use Elqora\Dgp\Deliveries\InitializationDelivery;
use Elqora\Dgp\Deliveries\FulfillmentDelivery;
use Elqora\Dgp\Errors\DgpError;
use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\Queries\DeliveryQuery;
use Elqora\Dgp\Runtime\References\DeliveryReference;
use Elqora\Dgp\Runtime\References\HandlerReference;
use Elqora\Dgp\Runtime\RuntimeWriteOptions;

class MockHandlerDeliveriesRepository implements HandlerDeliveriesRepositoryContract
{
    /**
     * @var array<string, mixed>
     */
    private array $store;
    private HandlerReference $handler;

    /**
     * @param array<string, mixed> $store
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

    public function findDelivery(DeliveryReference $reference): Result
    {
        foreach ($this->matchingDeliveries() as $delivery) {
            if ($reference->id !== null && $delivery->id == $reference->id) {
                return Result::success($delivery);
            }

            if ($reference->id === null && $reference->key !== null && $delivery->key === $reference->key) {
                return Result::success($delivery);
            }
        }

        /** @var Delivery|null $empty */
        $empty = null;
        return Result::success($empty);
    }

    public function deliveries(?DeliveryQuery $query = null): Result
    {
        $query ??= new DeliveryQuery();
        /** @var list<Delivery> $deliveries */
        $deliveries = [];

        foreach ($this->matchingDeliveries() as $delivery) {
            if ($query->status !== null && $delivery->status !== $query->status) {
                continue;
            }

            if ($query->active !== null && $this->isActive($delivery) !== $query->active) {
                continue;
            }

            $deliveries[] = $delivery;
            if ($query->limit !== null && count($deliveries) >= $query->limit) {
                break;
            }
        }

        return Result::success($deliveries);
    }

    /**
     * @return list<Delivery>
     */
    private function matchingDeliveries(): array
    {
        /** @var list<Delivery> $deliveries */
        $deliveries = [];

        foreach ($this->store['deliveries'] as $record) {
            if (!is_array($record) || ($record['handler'] ?? null) !== $this->getHandlerValue()) {
                continue;
            }

            /** @var Delivery $delivery */
            $delivery = $record['delivery'];
            $deliveries[] = $delivery;
        }

        return $deliveries;
    }

    private function isActive(Delivery $delivery): bool
    {
        return in_array($delivery->status, [DeliveryStatus::PENDING, DeliveryStatus::PROCESSING], true);
    }

    private function findOrderIdForDelivery(Delivery $delivery): string|int|null
    {
        $parentId = $delivery->planId ?? $delivery->startId;
        if ($parentId === null) {
            return null;
        }

        if ($delivery instanceof InitializationDelivery) {
            foreach ($this->store['plans'] ?? [] as $item) {
                if ($item['handler'] === $this->getHandlerValue() && $item['id'] == $parentId) {
                    return $item['order_id'];
                }
            }
        } elseif ($delivery instanceof FulfillmentDelivery) {
            foreach ($this->store['start_results'] ?? [] as $item) {
                if ($item['handler'] === $this->getHandlerValue() && $item['id'] == $parentId) {
                    return $item['order_id'];
                }
            }
        }

        return null;
    }

    public function addDelivery(
        Delivery $delivery,
        ?RuntimeWriteOptions $options = null,
    ): Result {
        $orderId = $this->findOrderIdForDelivery($delivery);
        if ($orderId === null) {
            /** @var Result<Delivery> $fail */
            $fail = Result::failure(new DgpError(
                code: 'parent_not_found',
                message: 'The referenced parent plan or start result does not exist or does not belong to this handler.'
            ));
            return $fail;
        }

        if ($delivery->id !== null) {
            foreach ($this->store['deliveries'] ?? [] as $item) {
                if ($item['handler'] === $this->getHandlerValue() && $item['id'] == $delivery->id) {
                    /** @var Result<Delivery> $fail */
                    $fail = Result::failure(new DgpError(
                        code: 'delivery_already_exists',
                        message: "Delivery with ID {$delivery->id} already exists."
                    ));
                    return $fail;
                }
            }
        }

        $this->store['delivery_auto_increment'] ??= 10000;
        $deliveryId = $delivery->id ?? $this->store['delivery_auto_increment']++;

        $parentId = $delivery->planId ?? $delivery->startId;
        if ($delivery instanceof InitializationDelivery) {
            $persistedDel = new InitializationDelivery(
                id: $deliveryId,
                key: $delivery->key,
                status: $delivery->status,
                label: $delivery->label,
                progress: $delivery->progress,
                planId: $parentId,
                nextAction: $delivery->nextAction,
                meta: $delivery->meta,
                kind: $delivery->kind,
                name: $delivery->name,
                isPublic: $delivery->isPublic,
                note: $delivery->note,
                buttons: $delivery->buttons,
            );
        } else {
            $persistedDel = new FulfillmentDelivery(
                id: $deliveryId,
                key: $delivery->key,
                status: $delivery->status,
                label: $delivery->label,
                progress: $delivery->progress,
                startId: $parentId,
                nextAction: $delivery->nextAction,
                meta: $delivery->meta,
                kind: $delivery->kind,
                name: $delivery->name,
                isPublic: $delivery->isPublic,
                note: $delivery->note,
                buttons: $delivery->buttons,
            );
        }

        $record = [
            'id' => $persistedDel->id,
            'key' => $persistedDel->key,
            'order_id' => $orderId,
            'handler' => $this->getHandlerValue(),
            'parent_id' => $parentId,
            'delivery' => $persistedDel
        ];

        $this->store['deliveries'][] = $record;

        return Result::success($persistedDel);
    }

    public function addDeliveries(
        array $deliveries,
        ?RuntimeWriteOptions $options = null,
    ): Result {
        $persisted = [];
        foreach ($deliveries as $delivery) {
            $res = $this->addDelivery($delivery, $options);
            if (!$res->isSuccess()) {
                /** @var Result<array<int, Delivery>> $fail */
                $fail = $res;
                return $fail;
            }
            $persisted[] = $res->value();
        }
        return Result::success($persisted);
    }

    public function addSegment(
        DeliveryReference $delivery,
        DeliveryProgressSegment $segment,
        ?RuntimeWriteOptions $options = null,
    ): Result {
        $foundIndex = null;
        $foundRecord = null;
        foreach ($this->store['deliveries'] ?? [] as $idx => $record) {
            if ($record['handler'] !== $this->getHandlerValue()) {
                continue;
            }
            $del = $record['delivery'];
            if ($delivery->id !== null && $del->id == $delivery->id) {
                $foundIndex = $idx;
                $foundRecord = $record;
                break;
            }
            if ($delivery->id === null && $delivery->key !== null && $del->key === $delivery->key) {
                $foundIndex = $idx;
                $foundRecord = $record;
                break;
            }
        }

        if ($foundRecord === null) {
            /** @var Result<DeliveryProgressSegment> $fail */
            $fail = Result::failure(new DgpError(
                code: 'delivery_not_found',
                message: 'Delivery not found.'
            ));
            return $fail;
        }

        /** @var Delivery $del */
        $del = $foundRecord['delivery'];

        $existingSegments = $del->progress?->segments ?? [];
        foreach ($existingSegments as $existing) {
            if ($existing->key === $segment->key) {
                /** @var Result<DeliveryProgressSegment> $fail */
                $fail = Result::failure(new DgpError(
                    code: 'segment_already_exists',
                    message: "Segment with key '{$segment->key}' already exists in this delivery."
                ));
                return $fail;
            }
        }

        $newSegments = array_merge($existingSegments, [$segment]);
        $newProgress = new DeliveryProgress(
            current: $del->progress?->current,
            target: $del->progress?->target,
            percent: $del->progress?->percent,
            unit: $del->progress?->unit,
            label: $del->progress?->label,
            meta: $del->progress?->meta,
            segments: $newSegments,
        );

        if ($del instanceof InitializationDelivery) {
            $updatedDel = new InitializationDelivery(
                id: $del->id,
                key: $del->key,
                status: $del->status,
                label: $del->label,
                progress: $newProgress,
                planId: $del->planId,
                nextAction: $del->nextAction,
                meta: $del->meta,
                kind: $del->kind,
                    name: $del->name,
                    isPublic: $del->isPublic,
                    note: $del->note,
                    buttons: $del->buttons,
                );
            } else {
            $updatedDel = new FulfillmentDelivery(
                id: $del->id,
                key: $del->key,
                status: $del->status,
                label: $del->label,
                progress: $newProgress,
                startId: $del->startId,
                nextAction: $del->nextAction,
                meta: $del->meta,
                kind: $del->kind,
                    name: $del->name,
                    isPublic: $del->isPublic,
                    note: $del->note,
                    buttons: $del->buttons,
                );
            }

        $foundRecord['delivery'] = $updatedDel;
        $this->store['deliveries'][$foundIndex] = $foundRecord;

        return Result::success($segment);
    }

    public function addSegments(
        DeliveryReference $delivery,
        array $segments,
        ?RuntimeWriteOptions $options = null,
    ): Result {
        $persisted = [];
        foreach ($segments as $segment) {
            $res = $this->addSegment($delivery, $segment, $options);
            if (!$res->isSuccess()) {
                /** @var Result<array<int, DeliveryProgressSegment>> $fail */
                $fail = $res;
                return $fail;
            }
            $persisted[] = $res->value();
        }
        return Result::success($persisted);
    }

    public function updateDeliveryStatus(
        DeliveryReference $delivery,
        DeliveryStatus $status,
        ?RuntimeWriteOptions $options = null,
    ): Result {
        $foundIndex = null;
        $foundRecord = null;
        foreach ($this->store['deliveries'] ?? [] as $idx => $record) {
            if ($record['handler'] !== $this->getHandlerValue()) {
                continue;
            }
            $del = $record['delivery'];
            if ($delivery->id !== null && $del->id == $delivery->id) {
                $foundIndex = $idx;
                $foundRecord = $record;
                break;
            }
            if ($delivery->id === null && $delivery->key !== null && $del->key === $delivery->key) {
                $foundIndex = $idx;
                $foundRecord = $record;
                break;
            }
        }

        if ($foundRecord === null) {
            /** @var Result<bool> $fail */
            $fail = Result::failure(new DgpError(
                code: 'delivery_not_found',
                message: 'Delivery not found.'
            ));
            return $fail;
        }

        /** @var Delivery $del */
        $del = $foundRecord['delivery'];

        if ($del instanceof InitializationDelivery) {
            $updatedDel = new InitializationDelivery(
                id: $del->id,
                key: $del->key,
                status: $status,
                label: $del->label,
                progress: $del->progress,
                planId: $del->planId,
                nextAction: $del->nextAction,
                meta: $del->meta,
                kind: $del->kind,
                name: $del->name,
                isPublic: $del->isPublic,
                note: $del->note,
                buttons: $del->buttons,
            );
        } else {
            $updatedDel = new FulfillmentDelivery(
                id: $del->id,
                key: $del->key,
                status: $status,
                label: $del->label,
                progress: $del->progress,
                startId: $del->startId,
                nextAction: $del->nextAction,
                meta: $del->meta,
                kind: $del->kind,
                name: $del->name,
                isPublic: $del->isPublic,
                note: $del->note,
                buttons: $del->buttons,
            );
        }

        $foundRecord['delivery'] = $updatedDel;
        $this->store['deliveries'][$foundIndex] = $foundRecord;

        return Result::success(true);
    }

    public function updateDeliveryVisibility(
        DeliveryReference $delivery,
        bool $isPublic,
        ?RuntimeWriteOptions $options = null,
    ): Result {
        $foundIndex = null;
        $foundRecord = null;
        foreach ($this->store['deliveries'] ?? [] as $idx => $record) {
            if ($record['handler'] !== $this->getHandlerValue()) {
                continue;
            }
            $del = $record['delivery'];
            if ($delivery->id !== null && $del->id == $delivery->id) {
                $foundIndex = $idx;
                $foundRecord = $record;
                break;
            }
            if ($delivery->id === null && $delivery->key !== null && $del->key === $delivery->key) {
                $foundIndex = $idx;
                $foundRecord = $record;
                break;
            }
        }

        if ($foundRecord === null) {
            /** @var Result<bool> $fail */
            $fail = Result::failure(new DgpError(
                code: 'delivery_not_found',
                message: 'Delivery not found.'
            ));
            return $fail;
        }

        /** @var Delivery $del */
        $del = $foundRecord['delivery'];

        if ($del instanceof InitializationDelivery) {
            $updatedDel = new InitializationDelivery(
                id: $del->id,
                key: $del->key,
                status: $del->status,
                label: $del->label,
                progress: $del->progress,
                planId: $del->planId,
                nextAction: $del->nextAction,
                meta: $del->meta,
                kind: $del->kind,
                name: $del->name,
                isPublic: $isPublic,
                note: $del->note,
                buttons: $del->buttons,
            );
        } else {
            $updatedDel = new FulfillmentDelivery(
                id: $del->id,
                key: $del->key,
                status: $del->status,
                label: $del->label,
                progress: $del->progress,
                startId: $del->startId,
                nextAction: $del->nextAction,
                meta: $del->meta,
                kind: $del->kind,
                name: $del->name,
                isPublic: $isPublic,
                note: $del->note,
                buttons: $del->buttons,
            );
        }

        $foundRecord['delivery'] = $updatedDel;
        $this->store['deliveries'][$foundIndex] = $foundRecord;

        return Result::success(true);
    }

    public function updateSegmentStatus(
        DeliveryReference $delivery,
        string $segmentKey,
        string $status,
        ?RuntimeWriteOptions $options = null,
    ): Result {
        $foundIndex = null;
        $foundRecord = null;
        foreach ($this->store['deliveries'] ?? [] as $idx => $record) {
            if ($record['handler'] !== $this->getHandlerValue()) {
                continue;
            }
            $del = $record['delivery'];
            if ($delivery->id !== null && $del->id == $delivery->id) {
                $foundIndex = $idx;
                $foundRecord = $record;
                break;
            }
            if ($delivery->id === null && $delivery->key !== null && $del->key === $delivery->key) {
                $foundIndex = $idx;
                $foundRecord = $record;
                break;
            }
        }

        if ($foundRecord === null) {
            /** @var Result<bool> $fail */
            $fail = Result::failure(new DgpError(
                code: 'delivery_not_found',
                message: 'Delivery not found.'
            ));
            return $fail;
        }

        /** @var Delivery $del */
        $del = $foundRecord['delivery'];

        $existingSegments = $del->progress?->segments ?? [];
        $segmentIndex = null;
        foreach ($existingSegments as $idx => $seg) {
            if ($seg->key === $segmentKey) {
                $segmentIndex = $idx;
                break;
            }
        }

        if ($segmentIndex === null) {
            /** @var Result<bool> $fail */
            $fail = Result::failure(new DgpError(
                code: 'segment_not_found',
                message: "Segment with key '{$segmentKey}' not found in this delivery."
            ));
            return $fail;
        }

        $targetSegment = $existingSegments[$segmentIndex];
        $updatedSegment = new DeliveryProgressSegment(
            key: $targetSegment->key,
            progress: $targetSegment->progress,
            label: $targetSegment->label,
            status: $status,
            sequence: $targetSegment->sequence,
            meta: $targetSegment->meta,
            isPublic: $targetSegment->isPublic,
            buttons: $targetSegment->buttons,
        );

        $newSegments = $existingSegments;
        $newSegments[$segmentIndex] = $updatedSegment;

        $newProgress = new DeliveryProgress(
            current: $del->progress?->current,
            target: $del->progress?->target,
            percent: $del->progress?->percent,
            unit: $del->progress?->unit,
            label: $del->progress?->label,
            meta: $del->progress?->meta,
            segments: $newSegments,
        );

        if ($del instanceof InitializationDelivery) {
            $updatedDel = new InitializationDelivery(
                id: $del->id,
                key: $del->key,
                status: $del->status,
                label: $del->label,
                progress: $newProgress,
                planId: $del->planId,
                nextAction: $del->nextAction,
                meta: $del->meta,
                kind: $del->kind,
                name: $del->name,
                isPublic: $del->isPublic,
                note: $del->note,
                buttons: $del->buttons,
            );
        } else {
            $updatedDel = new FulfillmentDelivery(
                id: $del->id,
                key: $del->key,
                status: $del->status,
                label: $del->label,
                progress: $newProgress,
                startId: $del->startId,
                nextAction: $del->nextAction,
                meta: $del->meta,
                kind: $del->kind,
                name: $del->name,
                isPublic: $del->isPublic,
                note: $del->note,
                buttons: $del->buttons,
            );
        }

        $foundRecord['delivery'] = $updatedDel;
        $this->store['deliveries'][$foundIndex] = $foundRecord;

        return Result::success(true);
    }

    public function updateSegmentVisibility(
        DeliveryReference $delivery,
        string $segmentKey,
        bool $isPublic,
        ?RuntimeWriteOptions $options = null,
    ): Result {
        $foundIndex = null;
        $foundRecord = null;
        foreach ($this->store['deliveries'] ?? [] as $idx => $record) {
            if ($record['handler'] !== $this->getHandlerValue()) {
                continue;
            }
            $del = $record['delivery'];
            if ($delivery->id !== null && $del->id == $delivery->id) {
                $foundIndex = $idx;
                $foundRecord = $record;
                break;
            }
            if ($delivery->id === null && $delivery->key !== null && $del->key === $delivery->key) {
                $foundIndex = $idx;
                $foundRecord = $record;
                break;
            }
        }

        if ($foundRecord === null) {
            /** @var Result<bool> $fail */
            $fail = Result::failure(new DgpError(
                code: 'delivery_not_found',
                message: 'Delivery not found.'
            ));
            return $fail;
        }

        /** @var Delivery $del */
        $del = $foundRecord['delivery'];

        $existingSegments = $del->progress?->segments ?? [];
        $segmentIndex = null;
        foreach ($existingSegments as $idx => $seg) {
            if ($seg->key === $segmentKey) {
                $segmentIndex = $idx;
                break;
            }
        }

        if ($segmentIndex === null) {
            /** @var Result<bool> $fail */
            $fail = Result::failure(new DgpError(
                code: 'segment_not_found',
                message: "Segment with key '{$segmentKey}' not found in this delivery."
            ));
            return $fail;
        }

        $targetSegment = $existingSegments[$segmentIndex];
        $updatedSegment = new DeliveryProgressSegment(
            key: $targetSegment->key,
            progress: $targetSegment->progress,
            label: $targetSegment->label,
            status: $targetSegment->status,
            sequence: $targetSegment->sequence,
            meta: $targetSegment->meta,
            isPublic: $isPublic,
            buttons: $targetSegment->buttons,
        );

        $newSegments = $existingSegments;
        $newSegments[$segmentIndex] = $updatedSegment;

        $newProgress = new DeliveryProgress(
            current: $del->progress?->current,
            target: $del->progress?->target,
            percent: $del->progress?->percent,
            unit: $del->progress?->unit,
            label: $del->progress?->label,
            meta: $del->progress?->meta,
            segments: $newSegments,
        );

        if ($del instanceof InitializationDelivery) {
            $updatedDel = new InitializationDelivery(
                id: $del->id,
                key: $del->key,
                status: $del->status,
                label: $del->label,
                progress: $newProgress,
                planId: $del->planId,
                nextAction: $del->nextAction,
                meta: $del->meta,
                kind: $del->kind,
                name: $del->name,
                isPublic: $del->isPublic,
                note: $del->note,
                buttons: $del->buttons,
            );
        } else {
            $updatedDel = new FulfillmentDelivery(
                id: $del->id,
                key: $del->key,
                status: $del->status,
                label: $del->label,
                progress: $newProgress,
                startId: $del->startId,
                nextAction: $del->nextAction,
                meta: $del->meta,
                kind: $del->kind,
                name: $del->name,
                isPublic: $del->isPublic,
                note: $del->note,
                buttons: $del->buttons,
            );
        }

        $foundRecord['delivery'] = $updatedDel;
        $this->store['deliveries'][$foundIndex] = $foundRecord;

        return Result::success(true);
    }
}
