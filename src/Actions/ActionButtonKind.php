<?php

namespace Elqora\Dgp\Actions;

enum ActionButtonKind: string
{
    case TEXT = 'text';
    case ICON = 'icon';
    case TEXT_ICON = 'text_icon';
}
