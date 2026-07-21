<?php

namespace Elqora\Dgp\Charges;

enum ChargeTargetType: string
{
    case PLAN = 'plan';
    case SEGMENT = 'segment';
    case DELIVERY = 'delivery';
}
