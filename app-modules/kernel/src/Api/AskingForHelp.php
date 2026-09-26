<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack for a support bundle, and asking what became of it.
 *
 * One port for describing a bundle and for writing one, because the stack
 * answers both through one action: the same choices, with writing asked for
 * or not. Both answer a handle, and the bundle arrives through it. It takes a
 * stack and a session rather than a client, for {@see TakingCopies}' reason.
 */
interface AskingForHelp
{
    /**
     * Ask for the bundle described by those choices, or come away with a reason.
     *
     * Answers {@see Underway}: the stack takes the work on and hands back
     * something to follow it by.
     */
    public function ask(Stack $stack, Session $session, ABundleAsked $asked): Underway;

    /** What became of a bundle asked for, by the handle asking for it answered. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheBundleIsGoing;
}
