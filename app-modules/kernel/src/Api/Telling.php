<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what its operator will be told about.
 *
 * **It reads and does not change anything.** What the operator hears about is
 * the core's decision, configured where the core is configured; this app shows
 * the setting and raises nothing of its own.
 *
 * It takes a stack and a session rather than a client, for the reason
 * {@see Asking} gives.
 */
interface Telling
{
    /** Ask a stack what its operator is told about, or come away with a reason (`C1`). */
    public function toldAbout(Stack $stack, Session $session): WhatTheAlertsWere;
}
