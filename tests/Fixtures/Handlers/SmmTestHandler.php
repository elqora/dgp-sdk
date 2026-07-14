<?php

namespace Elqora\Dgp\Tests\Fixtures\Handlers;

use Elqora\Dgp\Contracts\DgpDriverContract;
use Elqora\Dgp\Manifest\HandlerManifest;
use Elqora\Dgp\Manifest\Capability;
use Elqora\Dgp\Health\HandlerHealth;
use Elqora\Dgp\Health\HealthStatus;
use Elqora\Dgp\Health\HealthRequest;
use Elqora\Dgp\Balance\HandlerBalance;
use Elqora\Dgp\Balance\BalanceKind;
use Elqora\Dgp\Balance\BalanceRequest;
use Elqora\Dgp\Money\Money;
use Elqora\Dgp\Money\Amount;
use Elqora\Dgp\Money\Currency;
use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Catalog\Services\ServiceQuery;
use Elqora\Dgp\Catalog\Services\HandlerService;
use Elqora\Dgp\Runtime\InitializeRequest;
use Elqora\Dgp\Runtime\PrepareRequest;
use Elqora\Dgp\Runtime\PreparationResult;
use Elqora\Dgp\Runtime\PreparationStatus;
use Elqora\Dgp\Runtime\StartRequest;
use Elqora\Dgp\Runtime\SynchronizeRequest;
use Elqora\Dgp\Runtime\CancelRequest;
use Elqora\ConfigKit\Schema\ConfigSchema;
use Elqora\ConfigKit\Schema\UiConfigSchema;
use Elqora\ConfigKit\Schema\ConfigField;
use Elqora\ConfigKit\Support\ConfigBag;
use Elqora\ConfigKit\Support\ConfigValidationResult;
use Elqora\ConfigKit\Support\ConfigValidationError;
use Elqora\Dgp\Errors\DgpError;
use Elqora\Dgp\Runtime\Plan;
use Elqora\Dgp\Runtime\StartResult;
use Elqora\Dgp\Deliveries\DeliveryStatus;
use Elqora\Dgp\Deliveries\InitializationDelivery;
use Elqora\Dgp\Deliveries\ResolveOrderDeliveriesRequest;
use Elqora\Dgp\Management\ResolveOrderManagementRequest;
use Elqora\Dgp\Management\OrderManagement;
use Elqora\Dgp\Actions\GenericActionRequest;
use Elqora\Dgp\Bulk\CancelBulkRequest;
use Elqora\Dgp\Bulk\RefreshBulkRequest;
use Elqora\Dgp\Bulk\RetryBulkRequest;
use Elqora\Dgp\Bulk\StartBulkRequest;

class SmmTestHandler implements DgpDriverContract
{
    public function manifest(): Result
    {
        $manifest = new HandlerManifest(
            key: 'smm-test',
            name: 'SMM Test Handler',
            version: '1.0.0',
            capabilities: [
                Capability::CANCELLATION,
                Capability::SYNCHRONIZATION,
            ]
        );
        return Result::success($manifest);
    }

    public function configSchema(): ?ConfigSchema
    {
        return new ConfigSchema([
            new ConfigField(name: 'base_url', label: 'Base URL', required: true),
            new ConfigField(name: 'api_key', label: 'API Key', required: true, secret: true),
            new ConfigField(name: 'timeout', label: 'Timeout', type: 'number', required: false, default: 30),
            new ConfigField(name: 'sandbox_user', label: 'Sandbox User', required: false, sandbox: true),
        ]);
    }

    public function uiConfigSchema(): ?UiConfigSchema
    {
        return $this->configSchema()?->toUiConfigSchema();
    }

    public function validateConfig(
        ?ConfigBag $config = null,
    ): ConfigValidationResult {
        if ($config === null) {
            $res = new ConfigValidationResult(false);
            $res->addError('base_url', 'Base URL is required.');
            $res->addError('api_key', 'API Key is required.');
            return $res;
        }

        $res = new ConfigValidationResult(true);
        if (!$config->filledOption('base_url')) {
            $res->addError('base_url', 'Base URL is required.');
        }
        if (!$config->secret('api_key')) {
            $res->addError('api_key', 'API Key is required.');
        }
        if ($config->isSandbox() && !$config->filledOption('sandbox_user')) {
            $res->addError('sandbox_user', 'Sandbox User is required in sandbox mode.');
        }

        return $res;
    }

    /**
     * @return array<string, mixed>
     */
    public function publicConfig(
        ?ConfigBag $config = null,
    ): array {
        return $config?->toPublicArray() ?? [];
    }

    public function redactForLogs(mixed $payload): mixed
    {
        if (!is_array($payload)) {
            return $payload;
        }

        $redacted = $payload;

        foreach (['api_key', 'token', 'password', 'secret'] as $key) {
            if (array_key_exists($key, $redacted)) {
                $redacted[$key] = '[REDACTED]';
            }
        }

        // Handle nested arrays
        foreach ($redacted as $k => $v) {
            if (is_array($v)) {
                $redacted[$k] = $this->redactForLogs($v);
            }
        }

        return $redacted;
    }

    /**
     * @param HealthRequest|null $request
     * @return Result<\Elqora\Dgp\Health\HandlerHealth>
     */
    public function health(?HealthRequest $request = null): Result
    {
        return Result::success(new HandlerHealth(status: HealthStatus::OK));
    }

    /**
     * @param BalanceRequest|null $request
     * @return Result<\Elqora\Dgp\Balance\HandlerBalance>
     */
    public function balance(?BalanceRequest $request = null): Result
    {
        return Result::success(new HandlerBalance(
            kind: BalanceKind::FINITE,
            available: new Money(new Amount('500.00'), new Currency('USD'))
        ));
    }

    /**
     * @param ServiceQuery $query
     * @return Result<list<HandlerService>>
     */
    public function services(ServiceQuery $query): Result
    {
        /** @var list<HandlerService> $list */
        $list = [
            new HandlerService(
                id: 101,
                name: 'Instagram Likes',
                category: 'Instagram',
                capabilities: ['refill']
            )
        ];
        return Result::success($list);
    }

    /**
     * @return Result<\Elqora\Dgp\Runtime\Plan>
     */
    public function initialize(InitializeRequest $request): Result
    {
        $plan = new Plan(
            id: null,
            key: 'plan-smm-123',
            state: ['reserved_count' => 1000]
        );
        return Result::success($plan);
    }

    /**
     * @return Result<\Elqora\Dgp\Runtime\PreparationResult>
     */
    public function prepare(PrepareRequest $request): Result
    {
        /** @var string|int $planId */
        $planId = $request->plan->id;

        $deliveries = array_map(
            fn (InitializationDelivery $delivery): InitializationDelivery => new InitializationDelivery(
                id: $delivery->id,
                key: $delivery->key,
                status: DeliveryStatus::PROCESSING,
                label: $delivery->label,
                progress: $delivery->progress,
                planId: $delivery->planId,
                nextAction: $delivery->nextAction,
                meta: array_merge($delivery->meta, ['prepared' => true]),
                kind: $delivery->kind,
                name: $delivery->name,
                isPublic: $delivery->isPublic,
                note: $delivery->note,
            ),
            $request->plan->deliveries
        );

        return Result::success(new PreparationResult(
            planId: $planId,
            status: PreparationStatus::RUNNING,
            deliveries: $deliveries,
            state: ['prepared' => true]
        ));
    }

    /**
     * @return Result<\Elqora\Dgp\Runtime\StartResult>
     */
    public function start(StartRequest $request): Result
    {
        $startResult = new StartResult(
            id: null,
            key: 'start-smm-123',
            state: ['provider_order_id' => '998877']
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
     * @return Result<null>
     */
    public function handleGenericAction(GenericActionRequest $request): Result
    {
        return Result::success(null);
    }

    /**
     * @return Result<null>
     */
    public function startBulk(StartBulkRequest $request): Result
    {
        return Result::success(null);
    }

    /**
     * @return Result<null>
     */
    public function cancelBulk(CancelBulkRequest $request): Result
    {
        return Result::success(null);
    }

    /**
     * @return Result<null>
     */
    public function retryBulk(RetryBulkRequest $request): Result
    {
        return Result::success(null);
    }

    /**
     * @return Result<null>
     */
    public function refreshBulk(RefreshBulkRequest $request): Result
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
        $management = new OrderManagement(orderId: $request->snapshot->version === '1' ? '123' : '456');
        return Result::success($management);
    }
}
