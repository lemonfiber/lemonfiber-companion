<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what it is running, and telling it to change that.
 *
 * The app offers start, stop and restart by form and by service.
 * Reading and acting are one port rather than two because they are one
 * conversation: an operator reads a listing, picks a row, and says a verb — and
 * a port that only read would leave whoever built the acting half free to reach
 * a client of their own, which is what going through the SDK exists to prevent.
 *
 * **It takes a stack and a session rather than a client**, for the reason
 * {@see Asking} gives: each stack's session is separate and
 * every connection is pinned against that stack's fingerprint, so a port
 * taking a client would let a caller pair the two up wrongly.
 *
 * **Acting takes an {@see AgreedTo}, which rendering cannot produce.** That is
 * {@see Confirmed}'s argument applied to a verb: a disruptive
 * action to state what it disturbs before it is confirmed, and the way that
 * requirement is broken is never deliberate — a screen draws a row, the stop
 * button is right there, and a tap handler calls the thing that stops it.
 * Nothing in the code says *this was confirmed*, because nothing had to.
 *
 * **There is no `everything()`.** A caller that wants the whole stack stopped
 * says so form by form, which is the granularity named. A single verb
 * for the machine would be one tap between an operator and a house with nothing
 * working, and the confirmation would be asked about a list nobody could read
 * in one screen.
 */
interface Supervising
{
    /**
     * Read what the stack is running, or come away with a reason.
     *
     * Answers {@see WhatIsRunning} rather than raising, which `C1` requires: a
     * stack that is asleep, one on another network and one whose session has
     * ended are ordinary states of the world, and an operator is
     * told which of them they met.
     */
    public function running(Stack $stack, Session $session): WhatIsRunning;

    /**
     * The forms this stack has, a reading of its own.
     *
     * Apart from {@see running()} because they are another answer: a frame
     * reads a stack once, so a screen that needs both takes this one on a frame
     * of its own and holds it, while what is running is read again.
     */
    public function formsOn(Stack $stack, Session $session): WhatFormsThereAre;

    /**
     * Do what the operator agreed to, or come away with a reason.
     *
     * Answers {@see Underway} — the same value a repair answers with — because
     * the two are the same shape of act: the stack takes the work on and hands
     * back a name to ask about, or says why it will not. Reusing it is what
     * keeps one screen's *it is running* from meaning something different from
     * another's.
     */
    public function told(Stack $stack, Session $session, AgreedTo $agreed): Underway;

    /**
     * Ask the stack to rehearse what the operator is being asked to agree to.
     *
     * The same verb, run by the stack as a rehearsal: it reports what it would
     * do, the exact command included, and does none of it. Answered with a
     * handle, as the verb itself is, and followed with {@see whatBecameOf()}.
     * It changes nothing, so it may be asked again.
     */
    public function rehearsed(Stack $stack, Session $session, AgreedTo $agreed): Underway;

    /**
     * What became of a verb, by the handle telling the stack answered.
     *
     * A read: it asks after work the stack already named, and asking twice
     * changes nothing, so it carries no key.
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheVerbIsGoing;
}
