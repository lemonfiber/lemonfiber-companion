<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A subscription to what one stack says about its health, held by one screen.
 *
 * The core publishes its health summary on its event stream and nowhere else,
 * so a screen showing it holds the stream rather than reading. Holding it is
 * that screen's one read: the stack sends to every listener from the gather it
 * already runs, and taking what arrived sends nothing back.
 *
 * One of these belongs to one screen. It opens the subscription the first time
 * it is asked, and takes what has already arrived every time after that without
 * waiting for more. It is closed when the stack ends it, when it could not be
 * read, and when the screen lets go.
 */
interface Hearing
{
    public function howItIs(Stack $stack, Session $session): WhatWasHeard;

    public function letGo(): WhatWasHeard;
}
