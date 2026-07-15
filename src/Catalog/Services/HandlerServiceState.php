<?php

namespace Elqora\Dgp\Catalog\Services;

enum HandlerServiceState: string
{
    case ENABLED = 'enabled';
    case LOCKED = 'locked';
    case DISABLED = 'disabled';

    public function isSelectable(): bool
    {
        return $this === self::ENABLED;
    }
}
