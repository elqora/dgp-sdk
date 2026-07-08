<?php

namespace Elqora\Dgp\Errors;

final class DgpConfigurationException extends \LogicException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
