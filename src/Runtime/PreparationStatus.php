<?php

declare(strict_types=1);

namespace Elqora\Dgp\Runtime;

enum PreparationStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case ABANDONED = 'abandoned';
}
