<?php

namespace Elqora\Dgp\Catalog\Services\Contracts;

use Elqora\Dgp\Catalog\Services\HandlerService;
use Elqora\Dgp\Catalog\Services\ServiceQuery;
use Elqora\Dgp\Errors\Result;

interface HandlerServicesRepositoryContract
{
    /**
     * @param string|int $serviceId
     * @return Result<HandlerService|null>
     */
    public function findService(string|int $serviceId): Result;

    /**
     * @param ServiceQuery|null $query
     * @return Result<list<HandlerService>>
     */
    public function services(?ServiceQuery $query = null): Result;

    /**
     * @param string|int $serviceId
     * @param string $reason
     * @return Result<null>
     */
    public function enable(string|int $serviceId, string $reason): Result;

    /**
     * @param string|int $serviceId
     * @param string $reason
     * @return Result<null>
     */
    public function disable(string|int $serviceId, string $reason): Result;

    /**
     * @param string|int $serviceId
     * @param string $reason
     * @return Result<null>
     */
    public function lock(string|int $serviceId, string $reason): Result;

    /**
     * @param string|int $serviceId
     * @param string $reason
     * @return Result<null>
     */
    public function unlock(string|int $serviceId, string $reason): Result;

}
