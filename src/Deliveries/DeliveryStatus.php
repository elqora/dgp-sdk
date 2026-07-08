<?php

namespace Elqora\Dgp\Deliveries;

enum DeliveryStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
}
