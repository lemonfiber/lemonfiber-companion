<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a member's ask for a title came to, as the core answered it.
 */
enum WhatAnAskBecame: string
{
    /** It was asked for. */
    case Asked = 'asked';

    /** It was asked for, and waits for whoever runs the house. */
    case Waiting = 'waiting';

    /** Somebody already asked for it, it is on its way, and nothing new was made. */
    case OnItsWay = 'on-its-way';
}
