<?php

namespace Elqora\Dgp\Deliveries;

enum DeliveryStage: string
{
    case INITIALIZATION = 'initialization';
    case FULFILLMENT = 'fulfillment';
}
