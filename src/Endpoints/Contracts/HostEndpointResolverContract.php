<?php

namespace Elqora\Dgp\Endpoints\Contracts;

use Elqora\Dgp\Endpoints\HostEndpoint;
use Elqora\Dgp\Endpoints\HostEndpointType;

interface HostEndpointResolverContract
{
    public function endpoint(
        string $handler,
        HostEndpointType $type,
        ?string $asset = null,
    ): HostEndpoint;
}
