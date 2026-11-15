<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack how it runs without lemonfiber.
 *
 * It takes a stack and a session rather than a client, for the reason
 * {@see Asking} gives.
 */
interface AskingHowItRunsWithoutLemonfiber
{
    /** Ask a stack how it runs without lemonfiber, or come away with a reason. */
    public function withoutLemonfiber(Stack $stack, Session $session): WhatWasSaidOfRunningWithout;
}
