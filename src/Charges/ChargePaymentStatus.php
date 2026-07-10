<?php

namespace Elqora\Dgp\Charges;

enum ChargePaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case CANCELED = 'canceled';
}
