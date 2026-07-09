<?php

namespace Elqora\Dgp\Tests\Fixtures\Repository;

use Elqora\Dgp\Deliveries\Contracts\HandlerDeliveriesRepositoryContract;
use Elqora\Dgp\Deliveries\Delivery;
use Elqora\Dgp\Deliveries\DeliveryStatus;
use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\Queries\DeliveryQuery;
use Elqora\Dgp\Runtime\References\DeliveryReference;
use Elqora\Dgp\Runtime\References\HandlerReference;

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
}
