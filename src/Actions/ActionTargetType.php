<?php

namespace Elqora\Dgp\Actions;

enum ActionTargetType: string
{
    case ORDER = 'order';
    case PLAN = 'plan';
    case INITIALIZATION_DELIVERY = 'initialization_delivery';
    case FULFILLMENT_DELIVERY = 'fulfillment_delivery';
    case SEGMENT = 'segment';
    case CHARGE = 'charge';
    case MANAGEMENT = 'management';
}
