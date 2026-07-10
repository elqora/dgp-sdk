<?php

namespace Elqora\Dgp\Progress;

enum ProgressSource: string
{
    case HANDLER = 'handler';
    case SYNCHRONIZATION = 'synchronization';
    case WEBHOOK = 'webhook';
    case ACTION = 'action';
    case HOST = 'host';
    case MANUAL = 'manual';
}
