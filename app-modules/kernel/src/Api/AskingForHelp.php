<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack for a support bundle, asking what became of it, and fetching it once written.
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

    /**
     * The file of a bundle the stack wrote, fetched whole, or the obstacle met.
     *
     * A read, and nothing more: the file comes to this device so the operator
     * can hand it over through {@see Sharing::handOver()}, and this port sends
     * nothing of it anywhere.
     */
    public function fetch(Stack $stack, Session $session, AWrittenBundle $written): ABundleFetched;
}
