<?php

namespace Elqora\Dgp\Tests\Fixtures\Repository;

use Elqora\Dgp\Catalog\Services\Contracts\HandlerServicesRepositoryContract;
use Elqora\Dgp\Catalog\Services\HandlerService;
use Elqora\Dgp\Catalog\Services\ServiceQuery;
use Elqora\Dgp\Errors\DgpError;
use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\References\HandlerReference;

class MockHandlerServicesRepository implements HandlerServicesRepositoryContract
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

    private function key(string|int $serviceId): string
    {
        return $this->getHandlerValue() . ':' . (string) $serviceId;
    }

    public function findService(string|int $serviceId): Result
    {
        $record = $this->store['services'][$this->key($serviceId)] ?? null;

        /** @var HandlerService|null $service */
        $service = is_array($record) ? ($record['service'] ?? null) : null;
        return Result::success($service);
    }

    public function services(?ServiceQuery $query = null): Result
    {
        $query ??= new ServiceQuery();
        /** @var list<HandlerService> $services */
        $services = [];

        foreach ($this->store['services'] as $record) {
            if (!is_array($record) || ($record['handler'] ?? null) !== $this->getHandlerValue()) {
                continue;
            }

            /** @var HandlerService $service */
            $service = $record['service'];
            if ($query->category !== null && $service->category !== $query->category) {
                continue;
            }

            $matchesFilters = true;
            foreach ($query->filters as $key => $value) {
                if ($service->meta->getAny((string) $key) !== $value) {
                    $matchesFilters = false;
                    break;
                }
            }
            if (!$matchesFilters) {
                continue;
            }

            $services[] = $service;
            if (count($services) >= $query->limit) {
                break;
            }
        }

        return Result::success($services);
    }

    public function enable(string|int $serviceId, string $reason): Result
    {
        return $this->saveServiceState($serviceId, 'enabled', $reason);
    }

    public function disable(string|int $serviceId, string $reason): Result
    {
        return $this->saveServiceState($serviceId, 'disabled', $reason);
    }

    public function lock(string|int $serviceId, string $reason): Result
    {
        return $this->saveServiceState($serviceId, 'locked', $reason);
    }

    public function unlock(string|int $serviceId, string $reason): Result
    {
        return $this->saveServiceState($serviceId, 'unlocked', $reason);
    }

    /**
     * @return Result<null>|null
     */
    private function validateServiceStateUpdate(string|int $serviceId, string $reason): ?Result
    {
        if (is_string($serviceId) && trim($serviceId) === '') {
            return Result::failure(new DgpError(
                code: 'service_id_required',
                message: 'A service ID is required.'
            ));
        }

        if (trim($reason) === '') {
            return Result::failure(new DgpError(
                code: 'service_state_reason_required',
                message: 'A non-empty reason is required when changing service state.'
            ));
        }

        return null;
    }

    /**
     * @return Result<null>
     */
    private function saveServiceState(string|int $serviceId, string $state, string $reason): Result
    {
        $invalid = $this->validateServiceStateUpdate($serviceId, $reason);
        if ($invalid !== null) {
            return $invalid;
        }

        $this->store['service_states'][$this->key($serviceId)] = [
            'handler' => $this->getHandlerValue(),
            'service_id' => $serviceId,
            'state' => $state,
            'reason' => $reason,
        ];

        return Result::success(null);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function serviceState(string|int $serviceId): ?array
    {
        return $this->store['service_states'][$this->key($serviceId)] ?? null;
    }

}
