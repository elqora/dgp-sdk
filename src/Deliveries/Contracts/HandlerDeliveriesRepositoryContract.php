<?php

namespace Elqora\Dgp\Deliveries\Contracts;

use Elqora\Dgp\Deliveries\Delivery;
use Elqora\Dgp\Deliveries\DeliveryProgressSegment;
use Elqora\Dgp\Deliveries\DeliveryStatus;
use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\Queries\DeliveryQuery;
use Elqora\Dgp\Runtime\References\DeliveryReference;
use Elqora\Dgp\Runtime\RuntimeWriteOptions;

interface HandlerDeliveriesRepositoryContract
{
    /**
     * @param DeliveryReference $reference
     * @return Result<\Elqora\Dgp\Deliveries\Delivery|null>
     */
    public function findDelivery(DeliveryReference $reference): Result;

    /**
     * @param DeliveryQuery|null $query
     * @return Result<list<\Elqora\Dgp\Deliveries\Delivery>>
     */
    public function deliveries(?DeliveryQuery $query = null): Result;

    /**
     * @param Delivery $delivery
     * @param RuntimeWriteOptions|null $options
     * @return Result<Delivery>
     */
    public function addDelivery(
        Delivery $delivery,
        ?RuntimeWriteOptions $options = null,
    ): Result;

    /**
     * @param list<Delivery> $deliveries
     * @param RuntimeWriteOptions|null $options
     * @return Result<list<Delivery>>
     */
    public function addDeliveries(
        array $deliveries,
        ?RuntimeWriteOptions $options = null,
    ): Result;

    /**
     * @param DeliveryReference $delivery
     * @param DeliveryProgressSegment $segment
     * @param RuntimeWriteOptions|null $options
     * @return Result<DeliveryProgressSegment>
     */
    public function addSegment(
        DeliveryReference $delivery,
        DeliveryProgressSegment $segment,
        ?RuntimeWriteOptions $options = null,
    ): Result;

    /**
     * @param DeliveryReference $delivery
     * @param list<DeliveryProgressSegment> $segments
     * @param RuntimeWriteOptions|null $options
     * @return Result<list<DeliveryProgressSegment>>
     */
    public function addSegments(
        DeliveryReference $delivery,
        array $segments,
        ?RuntimeWriteOptions $options = null,
    ): Result;

    /**
     * @param DeliveryReference $delivery
     * @param DeliveryStatus $status
     * @param RuntimeWriteOptions|null $options
     * @return Result<bool>
     */
    public function updateDeliveryStatus(
        DeliveryReference $delivery,
        DeliveryStatus $status,
        ?RuntimeWriteOptions $options = null,
    ): Result;

    /**
     * @param DeliveryReference $delivery
     * @param bool $isPublic
     * @param RuntimeWriteOptions|null $options
     * @return Result<bool>
     */
    public function updateDeliveryVisibility(
        DeliveryReference $delivery,
        bool $isPublic,
        ?RuntimeWriteOptions $options = null,
    ): Result;

    /**
     * @param DeliveryReference $delivery
     * @param string $segmentKey
     * @param string $status
     * @param RuntimeWriteOptions|null $options
     * @return Result<bool>
     */
    public function updateSegmentStatus(
        DeliveryReference $delivery,
        string $segmentKey,
        string $status,
        ?RuntimeWriteOptions $options = null,
    ): Result;

    /**
     * @param DeliveryReference $delivery
     * @param string $segmentKey
     * @param bool $isPublic
     * @param RuntimeWriteOptions|null $options
     * @return Result<bool>
     */
    public function updateSegmentVisibility(
        DeliveryReference $delivery,
        string $segmentKey,
        bool $isPublic,
        ?RuntimeWriteOptions $options = null,
    ): Result;
}
