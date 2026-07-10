<?php

namespace Elqora\Dgp\Events;

enum EventType: string
{
    case INITIALIZED = 'initialized';
    case INITIALIZATION_DELIVERY_CREATED = 'initialization_delivery_created';
    case INITIALIZATION_DELIVERY_UPDATED = 'initialization_delivery_updated';
    case CHARGE_CREATED = 'charge_created';
    case CHARGE_PARTIALLY_PAID = 'charge_partially_paid';
    case CHARGE_PAID = 'charge_paid';
    case STARTED = 'started';
    case FULFILLMENT_DELIVERY_CREATED = 'fulfillment_delivery_created';
    case FULFILLMENT_DELIVERY_UPDATED = 'fulfillment_delivery_updated';
    case BULK_ACTION_RECEIVED = 'bulk_action_received';
    case BULK_ACTION_COMPLETED = 'bulk_action_completed';
    case MANAGEMENT_UPDATED = 'management_updated';
    case NEXT_ACTION_CREATED = 'next_action_created';
    case CANCELED = 'canceled';
    case FAILED = 'failed';
}
