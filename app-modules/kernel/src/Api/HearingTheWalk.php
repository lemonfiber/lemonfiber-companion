<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A subscription to the steps a running walkthrough says, held by the screen following it.
 *
 * The stack narrates each step of a walk on its event stream as it happens, and
 * nowhere else while the walk runs: asking after the walk's handle says only
 * that it is still running. So the screen following one holds the stream beside
 * the handle, and taking what arrived sends nothing back.
 *
 * {@see Hearing}'s shape, for a different thing said on the same stream. One of
 * these belongs to one screen. It opens the subscription the first time it is
 * asked, and takes what has already arrived every time after that without
 * waiting for more. It is closed when the stack ends it, when it could not be
 * read, and when the screen lets go.
 */
interface HearingTheWalk
{
    /** The last step said since the screen last asked, opening the subscription where it is not open. */
    public function whereItIs(Stack $stack, Session $session): WhatTheWalkSaid;

    public function letGo(): WhatTheWalkSaid;
}
