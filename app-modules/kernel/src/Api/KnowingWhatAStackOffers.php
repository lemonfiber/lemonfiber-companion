<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a stack says it can do, asked before an action is offered.
 *
 * The stack declares the requests it serves, and the app reads that rather
 * than working it out from a version number or by trying the action. Each
 * stack is asked for itself, so two stacks that differ in what they support
 * are never answered for one another, and each is asked with the session it
 * holds, because what an account may ask for is part of the answer.
 */
interface KnowingWhatAStackOffers
{
    /**
     * Whether this stack offers the action to this session.
     *
     * Asked from what is held of the stack. Where nothing is, the stack is
     * asked what it serves, which is the frame's one reading of it, and
     * {@see TheReadingWaitsAFrame} is raised so the button is drawn on the
     * next frame from the answer.
     */
    public function whetherItOffers(Stack $stack, Session $session, AnAction $action): WhetherItIsOffered;

    /**
     * Ask this stack again the next time anything is asked of it.
     *
     * What the operator's *ask again* means here: what a stack offers can
     * change under the app, configuring storage among the ways, and asking
     * again is the operator saying they no longer trust what was said.
     * Answers with what was let go of, which is nothing where the stack had
     * not been asked.
     */
    public function askAgain(StackId $stack): Forgotten;

    /**
     * A screen opens: let go of what any stack said longer ago than a break.
     *
     * What a stack serves is held while a screen is open, however long that
     * is, so an open screen never stops to ask it again. A screen opened after
     * a break asks again, before its first reading. Answers with what was let
     * go of.
     */
    public function aScreenOpens(): Forgotten;
}
