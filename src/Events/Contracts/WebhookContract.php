<?php

namespace Elqora\Dgp\Events\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Events\WebhookRequest;

interface WebhookContract
{
     /**
      * Handle webhook request received from the provider, verifying signatures and normalizing payload.
      *
      * @param WebhookRequest $request
      * @return Result<null>
      */
     public function handleWebhook(WebhookRequest $request): Result;
}
