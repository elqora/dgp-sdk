<?php

namespace Elqora\Dgp\Audits;

enum AuditLevel: string
{
    case INFO = 'info';
    case NOTICE = 'notice';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';
}
