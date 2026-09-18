<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what it would put right, and reading what came of it.
 *
 * The requirement is this: where the core offers a repair, the app offers it
 * too, and states what it does, what else it affects and whether it can be
 * undone *before* asking for confirmation. The types for that have been built
 * and tested since the health screen landed — {@see Repair} states all three
 * clauses and {@see Repairs} and {@see Offer} hold a listing — and the port was
 * held back, because the shape of the answer was not settled.
 *
 * **Two methods, because asking is not answering.** Every action on
 * this surface arrive as a job: the stack acknowledges and names the work, and
 * the outcome is a separate reading at a separate moment. A port with one
 * method would have to hide a wait inside itself — which is `F4`'s socket in a
 * frame and the second reading that is refused, both at once.
 *
 * **Asking what would be done changes nothing, and is still an action.** That
 * is the part worth knowing before reading the adapter: the unconfirmed form of
 * the repair action says what each repair *would* do and carries nothing out,
 * yet it is answered with a job like everything else here. So *what would you
 * put right* costs a round trip and a handle, and the screen that asks it has
 * to be built for waiting.
 *
 * **A handle is not a pending action.** Retaining an
 * undelivered action, replay one, or present one as pending. A job is none of
 * those — the stack received the action and named it, so asking after the name
 * is a read, and a read repeated changes nothing. {@see Job} says this at
 * more length, because it is the distinction that lets this port exist at all.
 */
interface Mending
{
    /**
     * Ask what this stack would put right, changing nothing.
     *
     * Answers a handle rather than a listing, which is the job shape showing through
     * the port rather than being hidden by it. A signature promising the
     * listing would be one that has to wait, and a port that waits is a screen
     * that freezes on a home network with a machine that may be asleep.
     */
    public function wouldPutRight(Stack $stack, Session $session): Underway;

    /**
     * Ask what became of that asking.
     *
     * Answers {@see HowTheOfferIsGoing} rather than raising, for `C1`'s reason:
     * still working, finished, and a job the stack no longer has an outcome for
     * are three ordinary states, and the third is the one an implementation is
     * most tempted to fold into one of the others.
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheOfferIsGoing;

    /**
     * Agree to one repair inside a listing, and have the stack carry it out.
     *
     * Takes a {@see Confirmed} rather than a repair and a listing, which is the
     * whole of confirming-is-not-viewing expressed as a signature: the only way to
     * make one is against a listing that holds the repair and a reading the
     * operator was actually shown, so a port taking the two separately would
     * let a caller agree to a repair on behalf of a listing it was never in.
     *
     * Answers a handle, like everything else here.
     */
    public function agreeTo(Stack $stack, Session $session, Confirmed $confirmed): Underway;

    /**
     * Ask what became of the carrying out.
     *
     * Its own method rather than {@see whatBecameOf()} widened, because the
     * answers are different things: one is a listing of what a stack *would*
     * do, the other a record of what it *did*. A single method answering either
     * would hand a screen a value it has to narrow before it can render it, and
     * the narrowing is where an offer gets shown as an outcome.
     */
    public function whatWasDoneAbout(Stack $stack, Session $session, Job $job): HowTheRepairIsGoing;
}
