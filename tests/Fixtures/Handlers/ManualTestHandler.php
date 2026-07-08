<?php

namespace Elqora\Dgp\Tests\Fixtures\Handlers;

use Elqora\Dgp\Contracts\DgpDriverContract;
use Elqora\Dgp\Catalog\Schemas\Contracts\ServiceSchemaCatalogContract;
use Elqora\Dgp\Manifest\HandlerManifest;
use Elqora\Dgp\Manifest\Capability;
use Elqora\Dgp\Health\HandlerHealth;
use Elqora\Dgp\Health\HealthStatus;
use Elqora\Dgp\Balance\HandlerBalance;
use Elqora\Dgp\Balance\BalanceKind;
use Elqora\Dgp\Money\Money;
use Elqora\Dgp\Money\Amount;
use Elqora\Dgp\Money\Currency;
use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Catalog\Services\ServiceQuery;
use Elqora\Dgp\Catalog\Services\HandlerService;
use Elqora\Dgp\Catalog\Schemas\ServiceSchemaQuery;
use Elqora\Dgp\Catalog\Schemas\ServiceSchemaDefinition;
use Elqora\Dgp\Catalog\Schemas\ServiceProps;
use Elqora\Dgp\Runtime\InitializeRequest;
use Elqora\Dgp\Runtime\StartRequest;
use Elqora\Dgp\Runtime\SynchronizeRequest;
use Elqora\Dgp\Runtime\CancelRequest;
use Elqora\ConfigKit\Schema\ConfigSchema;
use Elqora\ConfigKit\Schema\UiConfigSchema;
use Elqora\ConfigKit\Support\ConfigBag;
use Elqora\ConfigKit\Support\ConfigValidationResult;
use Elqora\Dgp\Runtime\Plan;
use Elqora\Dgp\Runtime\StartResult;
use Elqora\Dgp\Deliveries\ResolveOrderDeliveriesRequest;
use Elqora\Dgp\Management\ResolveOrderManagementRequest;
use Elqora\Dgp\Management\OrderManagement;

class ManualTestHandler implements DgpDriverContract, ServiceSchemaCatalogContract
{
    public function manifest(): Result
    {
        $manifest = new HandlerManifest(
            key: 'manual-test',
            name: 'Manual Test Handler',
            version: '1.0.0',
            capabilities: [
                Capability::SERVICE_SCHEMA_CATALOG,
                Capability::CHARGES,
            ]
        );
        return Result::success($manifest);
    }

    public function configSchema(): ?ConfigSchema
    {
        return null;
    }

    public function uiConfigSchema(): ?UiConfigSchema
    {
        return null;
    }

    public function validateConfig(
        ?ConfigBag $config = null,
    ): ConfigValidationResult {
        return ConfigValidationResult::ok();
    }

    /**
     * @return array<string, mixed>
     */
    public function publicConfig(
        ?ConfigBag $config = null,
    ): array {
        return [];
    }

    public function redactForLogs(mixed $payload): mixed
    {
        return $payload;
    }

    /**
     * @return Result<\Elqora\Dgp\Health\HandlerHealth>
     */
    public function health(): Result
    {
        return Result::success(new HandlerHealth(status: HealthStatus::OK));
    }

    /**
     * @return Result<\Elqora\Dgp\Balance\HandlerBalance>
     */
    public function balance(): Result
    {
        return Result::success(new HandlerBalance(kind: BalanceKind::UNLIMITED));
    }

    /**
     * @param ServiceQuery $query
     * @return Result<list<HandlerService>>
     */
    public function services(ServiceQuery $query): Result
    {
        /** @var list<HandlerService> $empty */
        $empty = [];
        return Result::success($empty);
    }

    /**
     * @param ServiceSchemaQuery $query
     * @return Result<list<ServiceSchemaDefinition>>
     */
    public function schemas(ServiceSchemaQuery $query): Result
    {
        $props = new ServiceProps(
            filters: [['id' => 'tag:manual', 'label' => 'Manual Task']],
            fields: [['id' => 'field:desc', 'type' => 'text', 'label' => 'Instructions']]
        );

        $schema = new ServiceSchemaDefinition(
            id: 'manual-task-1',
            name: 'Manual Custom Task',
            props: $props,
            schemaVersion: '1'
        );

        /** @var list<ServiceSchemaDefinition> $list */
        $list = [$schema];
        return Result::success($list);
    }

    /**
     * @return Result<\Elqora\Dgp\Runtime\Plan>
     */
    public function initialize(InitializeRequest $request): Result
    {
        $plan = new Plan(
            id: null,
            key: 'plan-manual-123',
            state: ['approved' => false]
        );
        return Result::success($plan);
    }

    /**
     * @return Result<\Elqora\Dgp\Runtime\StartResult>
     */
    public function start(StartRequest $request): Result
    {
        $startResult = new StartResult(
            id: null,
            key: 'start-manual-123',
            state: ['started_at' => '2026-07-07T12:00:00Z']
        );
        return Result::success($startResult);
    }

    /**
     * @return Result<null>
     */
    public function synchronize(SynchronizeRequest $request): Result
    {
        return Result::success(null);
    }

    /**
     * @return Result<null>
     */
    public function cancel(CancelRequest $request): Result
    {
        return Result::success(null);
    }

    /**
     * @return Result<list<\Elqora\Dgp\Deliveries\Delivery>>
     */
    public function resolveDeliveries(ResolveOrderDeliveriesRequest $request): Result
    {
        /** @var list<\Elqora\Dgp\Deliveries\Delivery> $empty */
        $empty = [];
        return Result::success($empty);
    }

    /**
     * @return Result<\Elqora\Dgp\Management\OrderManagement>
     */
    public function resolveManagement(ResolveOrderManagementRequest $request): Result
    {
        $management = new OrderManagement(orderId: 'manual-456');
        return Result::success($management);
    }
}
