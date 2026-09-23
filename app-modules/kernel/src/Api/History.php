<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what it has changed about itself.
 *
 * The question an operator asks after something moved and they did not move
 * it: what was done, by which operation, and whether it can be put back. It is
 * the other half of the hours nobody was looking — {@see Hosting} says what
 * was kept running, and this says what was changed.
 *
 * **It takes a stack and a session rather than a client**, for the reason
 * {@see Asking} gives: each stack's session is separate and every connection is
 * pinned against that stack's fingerprint, so a port taking a client would let
 * a caller pair the two up wrongly.
 *
 * **It reads and does not undo.** Putting a change back is an action with its
 * own agreement, and a record that offered it from a row would be a second
 * place that decision was made. The port has one method because it has one
 * question.
 */
interface History
{
    /**
     * Ask a stack for its record, or come away with a reason.
     *
     * Answers {@see WhatWasRecorded} rather than raising, which `C1` requires:
     * a stack that is asleep, one on another network and one whose session has
     * ended are ordinary states of the world.
     */
    public function recordedOn(Stack $stack, Session $session): WhatWasRecorded;
}
