<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What the household has asked a stack for.
 *
 * `N2-R11` is about the requests awaiting a decision, and until this existed
 * the app could reach a stack, read its health and say nothing at all about the
 * one thing the people in the house actually interact with. {@see Wanted},
 * {@see Size} and {@see Waiting} were written for it and reached by nothing.
 *
 * **It takes a stack and a session rather than a client**, for the reason
 * {@see Asking} gives: `N1-R11` keeps each stack's session separate and
 * `N1-R19` pins every connection against that stack's fingerprint, so a port
 * taking a client would let a caller pair the two up wrongly. This signature
 * makes that mistake unspellable.
 *
 * **One method, and it asks for the household rather than for one member.**
 * The endpoint narrows to a member by name, and this port does not offer it —
 * `N1-R65` has a screen read once per frame and render what came back, and
 * a port with a method per member is a screen opening a connection per row.
 * Narrowing is a question for whoever holds the answer, not another request.
 */
interface Wanting
{
    /**
     * Ask a stack what the household wants, or come away with a reason.
     *
     * Answers {@see WhatWasWanted} rather than raising, which `C1` requires:
     * a stack that is asleep, one on another network and one whose session has
     * ended are ordinary states of the world, and `N1-R10` says an operator is
     * told which of them they met.
     */
    public function askedOf(Stack $stack, Session $session): WhatWasWanted;
}
