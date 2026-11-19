<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what setup settled.
 *
 * It takes a stack and a session rather than a client, for the reason
 * {@see Asking} gives.
 */
interface AskingWhatSetupSettled
{
    /** Ask a stack what setup settled, or come away with a reason. */
    public function settled(Stack $stack, Session $session): WhatWasSaidOfTheSetup;
}
