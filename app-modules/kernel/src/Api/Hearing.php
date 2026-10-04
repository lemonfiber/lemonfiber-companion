<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A subscription to how one stack is, said on its event stream, held by one screen.
 *
 * The core publishes its health summary on its event stream and nowhere else,
 * and the newest of each kind it names the same way, so a screen showing
 * either holds the stream rather than reading. Holding it is that screen's one
 * read: the stack sends to every listener from the gather it already runs, and
 * taking what arrived sends nothing back.
 *
 * One of these belongs to one screen, and holds one subscription for each stack
 * it is asked about. It opens a stack's subscription the first time it is asked
 * about that stack, and takes what has already arrived every time after that
 * without waiting for more. A subscription is closed when the stack ends it and
 * when it could not be read, which leaves every other stack's open, and all of
 * them are closed when the screen lets go. What else the stream says is heard
 * on a subscription of its own: {@see HearingTheStart} and {@see HearingTheWalk}.
 */
interface Hearing
{
    public function howItIs(Stack $stack, Session $session): WhatWasHeard;

    public function letGo(): WhatWasHeard;
}
