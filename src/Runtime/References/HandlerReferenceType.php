<?php

namespace Elqora\Dgp\Runtime\References;

enum HandlerReferenceType: string
{
    case ID = 'id';
    case KEY = 'key';
    case ALIAS = 'alias';
}
