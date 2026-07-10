<?php

namespace Elqora\Dgp\Configuration;

use Elqora\Dgp\Catalog\Services\Contracts\ServicesRepositoryContract;
use Elqora\Dgp\Catalog\Services\Contracts\HandlerServicesRepositoryContract;
use Elqora\Dgp\Deliveries\Contracts\DeliveriesRepositoryContract;
use Elqora\Dgp\Deliveries\Contracts\HandlerDeliveriesRepositoryContract;
use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Errors\DgpError;
use Elqora\Dgp\Errors\DgpConfigurationException;
use Elqora\Dgp\Insights\Contracts\InsightsRepositoryContract;
use Elqora\Dgp\Insights\Contracts\HandlerInsightsRepositoryContract;
use Elqora\Dgp\Runtime\Contracts\RuntimeRepositoryContract;
use Elqora\Dgp\Runtime\Contracts\HandlerRuntimeRepositoryContract;
use Elqora\Dgp\Runtime\References\HandlerReference;
use Elqora\Dgp\Endpoints\HostEndpoint;
use Elqora\Dgp\Endpoints\HostEndpointType;

final class Dgp
{
    private static string $endpointPrefix = '/dgp';
    private static ?RuntimeRepositoryContract $runtimeRepository = null;
    private static ?ServicesRepositoryContract $servicesRepository = null;
    private static ?DeliveriesRepositoryContract $deliveriesRepository = null;
    private static ?InsightsRepositoryContract $insightsRepository = null;

    public static function endpointPrefix(string $prefix): void
    {
        self::$endpointPrefix = rtrim($prefix, '/');
    }

    public static function getEndpointPrefix(): string
    {
        return self::$endpointPrefix;
    }

    /**
     * Resolve a deterministic handler-scoped path for a given purpose.
     */
    public static function path(string $handler, string $purpose): string
    {
        return self::getEndpointPrefix() . '/' . urlencode($handler) . '/' . ltrim($purpose, '/');
    }

    public static function endpoint(
        string $handler,
        HostEndpointType $type,
        ?string $asset = null,
    ): HostEndpoint {
        $parameters = [];
        $purpose = match ($type) {
            HostEndpointType::DELIVERY_ACTION => 'delivery/action',
            HostEndpointType::DELIVERY_UPDATE => 'delivery/update',
            HostEndpointType::GENERIC_ACTION => 'generic/action',
            HostEndpointType::BULK_ACTION => 'bulk/action',
            HostEndpointType::CHARGE_UPDATE => 'charge/update',
            HostEndpointType::CHARGE_STATE => 'charge/state',
            HostEndpointType::WEBHOOK => 'webhook',
            HostEndpointType::MANAGEMENT_REFRESH => 'management/refresh',
            HostEndpointType::PRIVATE_ASSET => 'assets/' . rawurlencode((string) $asset),
        };

        if ($type === HostEndpointType::PRIVATE_ASSET) {
            $parameters['asset'] = $asset;
        }

        return new HostEndpoint(
            type: $type,
            handler: $handler,
            path: self::path($handler, $purpose),
            parameters: $parameters,
        );
    }

    public static function registerRuntimeRepository(
        RuntimeRepositoryContract $repository
    ): void {
        self::$runtimeRepository = $repository;
    }

    public static function registerServicesRepository(
        ServicesRepositoryContract $repository
    ): void {
        self::$servicesRepository = $repository;
    }

    public static function registerDeliveriesRepository(
        DeliveriesRepositoryContract $repository
    ): void {
        self::$deliveriesRepository = $repository;
    }

    public static function registerInsightsRepository(
        InsightsRepositoryContract $repository
    ): void {
        self::$insightsRepository = $repository;
    }

    /**
     * @return Result<HandlerRuntimeRepositoryContract>
     */
    public static function resolveRuntimeRepository(
        HandlerReference $handler
    ): Result {
        if (self::$runtimeRepository === null) {
            /** @var Result<HandlerRuntimeRepositoryContract> $fail */
            $fail = Result::failure(new DgpError(
                code: 'runtime_repository_not_registered',
                message: 'No DGP runtime repository has been registered.'
            ));
            return $fail;
        }
        return self::$runtimeRepository->forHandler($handler);
    }

    public static function runtimeRepository(
        HandlerReference $handler
    ): HandlerRuntimeRepositoryContract {
        if (self::$runtimeRepository === null) {
            throw new DgpConfigurationException(
                errorCode: 'runtime_repository_not_registered',
                message: 'No DGP runtime repository has been registered.'
            );
        }
        $result = self::$runtimeRepository->forHandler($handler);

        if ($result->isFailure()) {
            $error = $result->error();
            throw new DgpConfigurationException(
                errorCode: $error?->code ?? 'runtime_repository_resolution_failed',
                message: $error?->message ?? 'The handler runtime repository could not be resolved.',
            );
        }

        return $result->value();
    }

    /**
     * @return Result<HandlerServicesRepositoryContract>
     */
    public static function resolveServicesRepository(
        HandlerReference $handler
    ): Result {
        if (self::$servicesRepository === null) {
            /** @var Result<HandlerServicesRepositoryContract> $fail */
            $fail = Result::failure(new DgpError(
                code: 'services_repository_not_registered',
                message: 'No DGP services repository has been registered.'
            ));
            return $fail;
        }
        return self::$servicesRepository->forHandler($handler);
    }

    public static function servicesRepository(
        HandlerReference $handler
    ): HandlerServicesRepositoryContract {
        if (self::$servicesRepository === null) {
            throw new DgpConfigurationException(
                errorCode: 'services_repository_not_registered',
                message: 'No DGP services repository has been registered.'
            );
        }
        $result = self::$servicesRepository->forHandler($handler);

        if ($result->isFailure()) {
            $error = $result->error();
            throw new DgpConfigurationException(
                errorCode: $error?->code ?? 'services_repository_resolution_failed',
                message: $error?->message ?? 'The handler services repository could not be resolved.',
            );
        }

        return $result->value();
    }

    /**
     * @return Result<HandlerDeliveriesRepositoryContract>
     */
    public static function resolveDeliveriesRepository(
        HandlerReference $handler
    ): Result {
        if (self::$deliveriesRepository === null) {
            /** @var Result<HandlerDeliveriesRepositoryContract> $fail */
            $fail = Result::failure(new DgpError(
                code: 'deliveries_repository_not_registered',
                message: 'No DGP deliveries repository has been registered.'
            ));
            return $fail;
        }
        return self::$deliveriesRepository->forHandler($handler);
    }

    public static function deliveriesRepository(
        HandlerReference $handler
    ): HandlerDeliveriesRepositoryContract {
        if (self::$deliveriesRepository === null) {
            throw new DgpConfigurationException(
                errorCode: 'deliveries_repository_not_registered',
                message: 'No DGP deliveries repository has been registered.'
            );
        }
        $result = self::$deliveriesRepository->forHandler($handler);

        if ($result->isFailure()) {
            $error = $result->error();
            throw new DgpConfigurationException(
                errorCode: $error?->code ?? 'deliveries_repository_resolution_failed',
                message: $error?->message ?? 'The handler deliveries repository could not be resolved.',
            );
        }

        return $result->value();
    }

    /**
     * @return Result<HandlerInsightsRepositoryContract>
     */
    public static function resolveInsightsRepository(
        HandlerReference $handler
    ): Result {
        if (self::$insightsRepository === null) {
            /** @var Result<HandlerInsightsRepositoryContract> $fail */
            $fail = Result::failure(new DgpError(
                code: 'insights_repository_not_registered',
                message: 'No DGP insights repository has been registered.'
            ));
            return $fail;
        }
        return self::$insightsRepository->forHandler($handler);
    }

    public static function insightsRepository(
        HandlerReference $handler
    ): HandlerInsightsRepositoryContract {
        if (self::$insightsRepository === null) {
            throw new DgpConfigurationException(
                errorCode: 'insights_repository_not_registered',
                message: 'No DGP insights repository has been registered.'
            );
        }
        $result = self::$insightsRepository->forHandler($handler);

        if ($result->isFailure()) {
            $error = $result->error();
            throw new DgpConfigurationException(
                errorCode: $error?->code ?? 'insights_repository_resolution_failed',
                message: $error?->message ?? 'The handler insights repository could not be resolved.',
            );
        }

        return $result->value();
    }
}
