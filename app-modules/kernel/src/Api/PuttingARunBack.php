<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Telling a stack to put back one run of changes it recorded, and asking what became of it.
 *
 * One conversation, so one port, for the reason {@see KeepingCurrent} gives.
 *
 * **The yes takes the record's own rows.** The stack offers no rehearsal of
 * this over the wire and asks for no confirmation, so what the operator agrees
 * to is what the record already says: which changes carry the run's stamp and
 * how many go with it. {@see ARunAgreedTo} can only be built from that.
 */
interface PuttingARunBack
{
    /**
     * Put the run back, or come away with a reason.
     *
     * Answers {@see Underway}: the stack takes the work on and hands back
     * something to follow it by.
     */
    public function putBack(Stack $stack, Session $session, ARunAgreedTo $agreed): Underway;

    /** What became of putting it back, by the handle agreeing answered. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowPuttingARunBackIsGoing;
}
