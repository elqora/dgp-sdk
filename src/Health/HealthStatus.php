<?php

namespace Elqora\Dgp\Health;

enum HealthStatus: string
{
    case OK = 'ok';
    case DEGRADED = 'degraded';
    case FAIL = 'fail';
}
