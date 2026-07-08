<?php

namespace Elqora\Dgp\Assets\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Assets\ResolvePrivateAssetRequest;

interface PrivateAssetContract
{
    /**
     * Resolve a private asset from the handler, returning a portable private-asset descriptor.
     *
     * @param ResolvePrivateAssetRequest $request
     * @return Result<\Elqora\Dgp\Assets\PrivateAsset>
     */
    public function resolve(ResolvePrivateAssetRequest $request): Result;
}
