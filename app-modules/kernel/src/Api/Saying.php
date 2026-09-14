<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what one of its services has been saying.
 *
 * `N2-R10` asks for logs to be offered as a bounded, searchable read that names
 * the service and states that the view is a window rather than the whole. This
 * is the asking half; the other three clauses are held by {@see Scrollback},
 * where they cannot be dropped by a screen.
 *
 * **It takes a stack and a session rather than a client**, for the reason
 * {@see Asking} gives: `N1-R11` keeps each stack's session separate and
 * `N1-R19` pins every connection against that stack's fingerprint, so a port
 * taking a client would let a caller pair the two up wrongly. This signature
 * makes that mistake unspellable.
 *
 * **One service and one bound, both required.** Neither has a default here. A
 * window over no service is the whole machine talking at once, which is what a
 * terminal is for; a bound the port chose is a port deciding how much of
 * somebody's phone screen to fill. The SDK's own `Logs` insists on the same two
 * for the same reasons, and a port that softened either would be undoing that
 * one layer up.
 *
 * **No follow.** Asking a stack to keep reading is not this call with a flag on
 * it — the answer stops being lines and becomes a name for work that will not
 * end, which is a different shape a caller would have to branch on before it
 * could read what it got. `N1-R41` also has a bearing: this app declines rather
 * than holds, and a stream is a held thing by definition.
 */
interface Saying
{
    /**
     * Read the tail of one service, or come away with a reason.
     *
     * Answers {@see WhatWasSaid} rather than raising, which `C1` requires: a
     * stack that is asleep, one on another network and one whose session has
     * ended are ordinary states of the world, and `N1-R10` says an operator is
     * told which of them they met.
     */
    public function saidBy(Stack $stack, Session $session, ServiceId $service, HowManyLines $lines): WhatWasSaid;
}
