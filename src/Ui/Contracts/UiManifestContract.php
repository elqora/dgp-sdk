<?php

namespace Elqora\Dgp\Ui\Contracts;

use Elqora\Dgp\Errors\Result;

interface UiManifestContract
{
    /**
     * Get the UI manifest.
     *
     * @return Result<array<string, mixed>>
     */
    public function uiManifest(): Result;
}
