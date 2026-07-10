<?php

namespace Elqora\Dgp\Endpoints;

enum HostEndpointType: string
{
    case DELIVERY_ACTION = 'delivery.action';
    case DELIVERY_UPDATE = 'delivery.update';
    case GENERIC_ACTION = 'generic.action';
    case BULK_ACTION = 'bulk.action';
    case CHARGE_UPDATE = 'charge.update';
    case CHARGE_STATE = 'charge.state';
    case WEBHOOK = 'webhook.receive';
    case MANAGEMENT_REFRESH = 'management.refresh';
    case PRIVATE_ASSET = 'private.asset';
}
