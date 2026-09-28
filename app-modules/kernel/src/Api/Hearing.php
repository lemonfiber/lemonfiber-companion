<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A subscription to what one stack says on its event stream, held by one screen.
 *
 * The core publishes its health summary on its event stream and nowhere else,
 * and what a running start is waiting for the same way, so a screen showing
 * either holds the stream rather than reading. Holding it is
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

    /**
     * What the stack last said a start is waiting for, from the same stream.
     *
     * The stream carries a line for each thing a start waits on, as it waits
     * on it, and the newest replaces the one before. One subscription answers
     * one of these two questions: a screen following a start asks this, and a
     * screen showing the health summary asks {@see howItIs()}.
     */
    public function whatAStartWaitsOn(Stack $stack, Session $session): WhatAStartWaitsOn;

    public function letGo(): WhatWasHeard;
}
