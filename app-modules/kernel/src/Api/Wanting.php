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
 * **Two methods: a reading and a decision.** `N2-R11` asks that a waiting
 * request be surfaced with enough to decide on *and* be approvable and
 * refusable from the app, and for a long time this port did only the first —
 * so the screen said *waiting for your decision* and offered nothing to decide
 * with. The two are one errand from an operator's side and two calls on the
 * wire, which is why they are two methods rather than one.
 *
 * **The reading asks for the household rather than for one member.**
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

    /**
     * Tell a stack what the operator decided about one of them (`N2-R11`).
     *
     * Answers {@see Underway} rather than raising, for the reason the reading
     * does: a stack asleep and a session that has ended are states of the
     * world. The decision arrives as a {@see Decided}, which cannot be built
     * without naming both the request and what was decided about it — so a
     * screen cannot hand this a request it never read, and cannot decline one
     * without the sentence `D7-R7` owes the person who asked.
     */
    public function decided(Stack $stack, Session $session, Decided $decided): Underway;
}
