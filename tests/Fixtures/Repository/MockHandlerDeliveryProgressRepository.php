<?php

namespace Elqora\Dgp\Tests\Fixtures\Repository;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Progress\Contracts\HandlerDeliveryProgressRepositoryContract;
use Elqora\Dgp\Progress\DeliveryProgressRecord;
use Elqora\Dgp\Progress\ProgressTimelineQuery;
use Elqora\Dgp\Runtime\References\DeliveryReference;
use Elqora\Dgp\Runtime\References\HandlerReference;

class MockHandlerDeliveryProgressRepository implements HandlerDeliveryProgressRepositoryContract
{
    /**
     * @var array<string, mixed>
     */
    private array $store;
    private HandlerReference $handler;
    private static int $autoIncrement = 5000;

    /**
     * @param array<string, mixed> $store
     */
    public function __construct(array &$store, HandlerReference $handler)
    {
        $this->store = &$store;
        $this->store['progress_records'] ??= [];
        $this->handler = $handler;
    }

    public function record(DeliveryProgressRecord $record): Result
    {
        $persisted = $record->id === null
            ? new DeliveryProgressRecord(
                id: self::$autoIncrement++,
                orderId: $record->orderId,
                delivery: $record->delivery,
                stage: $record->stage,
                progress: $record->progress,
                recordedAt: $record->recordedAt,
                source: $record->source,
                meta: $record->meta,
            )
            : $record;

        $this->store['progress_records'][] = [
            'handler' => $this->handler->value,
            'record' => $persisted,
        ];

        return Result::success($persisted);
    }

    public function timeline(
        DeliveryReference $delivery,
        ?ProgressTimelineQuery $query = null,
    ): Result {
        return Result::success($this->filterRecords($query, $delivery));
    }

    public function timelineForOrder(
        string|int $orderId,
        ?ProgressTimelineQuery $query = null,
    ): Result {
        $records = $this->filterRecords($query, orderId: $orderId);

        return Result::success($records);
    }

    public function recordSegmentProgress(
        DeliveryReference $delivery,
        string $segmentKey,
        DeliveryProgressRecord $record,
    ): Result {
        $persisted = $record->id === null
            ? new DeliveryProgressRecord(
                id: self::$autoIncrement++,
                orderId: $record->orderId,
                delivery: $delivery,
                stage: $record->stage,
                progress: $record->progress,
                recordedAt: $record->recordedAt,
                source: $record->source,
                meta: $record->meta,
                segmentKey: $segmentKey,
            )
            : new DeliveryProgressRecord(
                id: $record->id,
                orderId: $record->orderId,
                delivery: $delivery,
                stage: $record->stage,
                progress: $record->progress,
                recordedAt: $record->recordedAt,
                source: $record->source,
                meta: $record->meta,
                segmentKey: $segmentKey,
            );

        $this->store['progress_records'][] = [
            'handler' => $this->handler->value,
            'record' => $persisted,
        ];

        return Result::success($persisted);
    }

    /**
     * @return list<DeliveryProgressRecord>
     */
    private function filterRecords(
        ?ProgressTimelineQuery $query = null,
        ?DeliveryReference $delivery = null,
        string|int|null $orderId = null,
    ): array
    {
        $query ??= new ProgressTimelineQuery();
        $records = [];

        foreach ($this->store['progress_records'] as $item) {
            if (!is_array($item) || ($item['handler'] ?? null) !== $this->handler->value) {
                continue;
            }

            /** @var DeliveryProgressRecord $record */
            $record = $item['record'];

            if ($delivery !== null && !$this->matchesDelivery($record, $delivery)) {
                continue;
            }

            if ($orderId !== null && $record->orderId != $orderId) {
                continue;
            }

            if ($query->source !== null && $record->source !== $query->source) {
                continue;
            }

            if ($query->from !== null && strcmp($record->recordedAt, $query->from) < 0) {
                continue;
            }

            if ($query->to !== null && strcmp($record->recordedAt, $query->to) > 0) {
                continue;
            }

            if ($query->segmentKey !== null && $record->segmentKey !== $query->segmentKey) {
                continue;
            }

            $records[] = $record;
        }

        usort(
            $records,
            static fn (DeliveryProgressRecord $a, DeliveryProgressRecord $b): int => strcmp($a->recordedAt, $b->recordedAt)
        );

        if (!$query->ascending) {
            $records = array_reverse($records);
        }

        if ($query->limit !== null) {
            $records = array_slice($records, 0, $query->limit);
        }

        return $records;
    }

    private function matchesDelivery(DeliveryProgressRecord $record, DeliveryReference $delivery): bool
    {
        if ($delivery->id !== null && $record->delivery->id == $delivery->id) {
            return true;
        }

        return $delivery->id === null
            && $delivery->key !== null
            && $record->delivery->key === $delivery->key;
    }
}
