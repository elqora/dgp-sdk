<?php

namespace Elqora\Dgp\Configuration;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Errors\DgpError;
use Elqora\Dgp\Errors\DgpConfigurationException;
use Elqora\Dgp\Runtime\Contracts\RuntimeRepositoryContract;
use Elqora\Dgp\Runtime\Contracts\HandlerRuntimeRepositoryContract;
use Elqora\Dgp\Runtime\References\HandlerReference;

final class Dgp
{
    private static string $endpointPrefix = '/dgp';
    private static ?RuntimeRepositoryContract $runtimeRepository = null;

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

    public static function registerRuntimeRepository(
        RuntimeRepositoryContract $repository
    ): void {
        self::$runtimeRepository = $repository;
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
}
