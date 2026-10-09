<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack for a grant to play a member's titles on this device.
 *
 * The core does the whole of it: it asks the media server for a session on the
 * member's own account and answers the session's token once, so the member's
 * age limit and libraries apply to everything played with it and this app
 * never speaks to the media server. Asking again for the same device replaces
 * the session the core opened for it before.
 */
interface Granting
{
    /** A grant for this device on the signed-in member's account, or why there is none. */
    public function aGrantFor(Stack $stack, Session $session, ThisDevice $device): WhatTheGrantCameTo;
}
