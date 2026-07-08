<?php

namespace Elqora\Dgp\Charges;

enum ChargeStatus: string
{
    case PENDING = 'pending';
    case INVOICED = 'invoiced';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case CANCELED = 'canceled';
}
