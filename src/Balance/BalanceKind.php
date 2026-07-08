<?php

namespace Elqora\Dgp\Balance;

enum BalanceKind: string
{
    case FINITE = 'finite';
    case UNLIMITED = 'unlimited';
}
