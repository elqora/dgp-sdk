<?php

namespace Elqora\Dgp\Manifest\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Manifest\HandlerManifest;

interface ManifestContract
{
    /**
     * Get the handler manifest.
     *
     * @return Result<HandlerManifest>
     */
    public function manifest(): Result;
}
