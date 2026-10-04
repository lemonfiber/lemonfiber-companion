<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A subscription to what a running start is waiting for, held by the screen that sent it.
 *
 * The stream carries a line for each thing a start waits on, as it waits on
 * it, and the newest replaces the one before. So the screen that sent a start
 * or a restart holds the stream while the verb runs, and taking what arrived
 * sends nothing back.
 *
 * {@see Hearing}'s shape, for a different thing said on the same stream. One of
 * these belongs to one screen, beside whatever else that screen hears: taking
 * what arrived on a stream takes all of it, so each thing a screen hears is
 * heard on a subscription of its own. It opens the subscription the first time
 * it is asked, and takes what has already arrived every time after that
 * without waiting for more. It is closed when the stack ends it, when it could
 * not be read, and when the screen lets go.
 */
interface HearingTheStart
{
    /** The newest line said since the screen last asked, opening the subscription where it is not open. */
    public function whatItWaitsOn(Stack $stack, Session $session): WhatAStartWaitsOn;

    /** Let go of the subscription, after which nothing new has been heard. */
    public function letGo(): WhatAStartWaitsOn;
}
