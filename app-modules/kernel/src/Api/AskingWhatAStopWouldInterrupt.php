<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what is still coming down inside what an action would stop.
 *
 * It takes a stack and a session rather than a client, for the reason
 * {@see Asking} gives.
 */
interface AskingWhatAStopWouldInterrupt
{
    /** Ask what stopping that would interrupt, or come away with a reason. */
    public function inside(Stack $stack, Session $session, WhatAStopReaches $reach): WhatWasFoundComingDown;
}
