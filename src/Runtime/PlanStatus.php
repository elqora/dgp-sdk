<?php

declare(strict_types=1);

namespace Elqora\Dgp\Runtime;

enum PlanStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case ABANDONED = 'abandoned';
}
