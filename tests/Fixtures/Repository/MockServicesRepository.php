<?php

namespace Elqora\Dgp\Tests\Fixtures\Repository;

use Elqora\Dgp\Catalog\Services\HandlerService;
use Elqora\Dgp\Catalog\Services\Contracts\HandlerServicesRepositoryContract;
use Elqora\Dgp\Catalog\Services\Contracts\ServicesRepositoryContract;
use Elqora\Dgp\Errors\DgpError;
use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\References\HandlerReference;

class MockServicesRepository implements ServicesRepositoryContract
{
    /**
     * @var array<string, mixed>
     */
    private array $store;

    /**
     * @param array<string, mixed> $store
     */
    public function __construct(array &$store)
    {
        $this->store = &$store;
    }

    public function forHandler(HandlerReference $handler): Result
    {
        if ($handler->value === 'unknown-handler') {
            /** @var Result<HandlerServicesRepositoryContract> $fail */
            $fail = Result::failure(new DgpError(
                code: 'unknown_handler',
                message: 'Unknown handler reference provided.'
            ));
            return $fail;
        }

        return Result::success(
            new MockHandlerServicesRepository($this->store, $handler)
        );
    }

    /**
     * @param list<HandlerService> $services
     */
    public function seedServices(HandlerReference $handler, array $services): void
    {
        foreach ($services as $service) {
            $key = $handler->value . ':' . (string) $service->id;
            $this->store['services'][$key] = [
                'handler' => $handler->value,
                'service_id' => $service->id,
                'service' => $service,
            ];
        }
    }
}
