<?php

namespace Elqora\Dgp\Tests\Fixtures\Repository;

use Elqora\Dgp\Audits\AuditQuery;
use Elqora\Dgp\Audits\AuditRecord;
use Elqora\Dgp\Audits\Contracts\HandlerAuditRepositoryContract;
use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\References\DeliveryReference;
use Elqora\Dgp\Runtime\References\HandlerReference;

class MockHandlerAuditRepository implements HandlerAuditRepositoryContract
{
    /**
     * @var array<string, mixed>
     */
    private array $store;
    private HandlerReference $handler;
    private static int $autoIncrement = 7000;

    /**
     * @param array<string, mixed> $store
     */
    public function __construct(array &$store, HandlerReference $handler)
    {
        $this->store = &$store;
        $this->store['audit_records'] ??= [];
        $this->handler = $handler;
    }

    public function record(AuditRecord $record): Result
    {
        $persisted = $record->id === null
            ? new AuditRecord(
                id: self::$autoIncrement++,
                key: $record->key,
                level: $record->level,
                message: $record->message,
                occurredAt: $record->occurredAt,
                orderId: $record->orderId,
                delivery: $record->delivery,
                category: $record->category,
                code: $record->code,
                context: $record->context,
                meta: $record->meta,
            )
            : $record;

        $this->store['audit_records'][] = [
            'handler' => $this->handler->value,
            'record' => $persisted,
        ];

        return Result::success($persisted);
    }

    public function records(?AuditQuery $query = null): Result
    {
        return Result::success($this->filterRecords($query));
    }

    public function recordsForOrder(
        string|int $orderId,
        ?AuditQuery $query = null,
    ): Result {
        return Result::success($this->filterRecords($query, orderId: $orderId));
    }

    /**
     * @return list<AuditRecord>
     */
    private function filterRecords(
        ?AuditQuery $query = null,
        string|int|null $orderId = null,
    ): array
    {
        $query ??= new AuditQuery();
        $records = [];
        $effectiveOrderId = $orderId ?? $query->orderId;

        foreach ($this->store['audit_records'] as $item) {
            if (!is_array($item) || ($item['handler'] ?? null) !== $this->handler->value) {
                continue;
            }

            /** @var AuditRecord $record */
            $record = $item['record'];

            if ($effectiveOrderId !== null && $record->orderId != $effectiveOrderId) {
                continue;
            }

            if ($query->delivery !== null && !$this->matchesDelivery($record, $query->delivery)) {
                continue;
            }

            if ($query->level !== null && $record->level !== $query->level) {
                continue;
            }

            if ($query->category !== null && $record->category !== $query->category) {
                continue;
            }

            if ($query->code !== null && $record->code !== $query->code) {
                continue;
            }

            if ($query->from !== null && strcmp($record->occurredAt, $query->from) < 0) {
                continue;
            }

            if ($query->to !== null && strcmp($record->occurredAt, $query->to) > 0) {
                continue;
            }

            $records[] = $record;
        }

        usort(
            $records,
            static fn (AuditRecord $a, AuditRecord $b): int => strcmp($a->occurredAt, $b->occurredAt)
        );

        if (!$query->ascending) {
            $records = array_reverse($records);
        }

        if ($query->limit !== null) {
            $records = array_slice($records, 0, $query->limit);
        }

        return $records;
    }

    private function matchesDelivery(AuditRecord $record, DeliveryReference $delivery): bool
    {
        if ($record->delivery === null) {
            return false;
        }

        if ($delivery->id !== null && $record->delivery->id == $delivery->id) {
            return true;
        }

        return $delivery->id === null
            && $delivery->key !== null
            && $record->delivery->key === $delivery->key;
    }
}
