<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What the stack says a member is owed, asked before they ask for anything.
 *
 * One question, and the timing is the requirement rather than a nicety: a
 * member is told whether what they want needs approval and whether they have
 * allowance left *before* they ask, and told when a spent allowance comes back
 * rather than being left to ask again every hour.
 *
 * **It answers with the core's sentences and not with the parts.** A port
 * handing back a policy and a standing and two counts would be inviting every
 * surface to write its own wording for them, and the wordings would differ from
 * each other and from the core's. {@see WhatTheyAreOwed} is what the core wrote.
 *
 * **Per stack, because a person is a member of one house at a time.** The
 * reading belongs to the stack that holds the account, and a port taking no
 * stack would be answering about whichever one was reached first.
 *
 * **Narrowed by the core rather than here.** A session that belongs to a member
 * is answered with that member's reading, because the core was asked for one —
 * not because this app filtered a list it should not have been holding. An app
 * that picked a member out of the household would be choosing who is looking,
 * which is the answer the core is there to give.
 */
interface Owing
{
    /**
     * What this stack says the signed-in member is owed.
     *
     * Answers {@see WhatTheyAreOwed} rather than raising, which `C1` requires:
     * a stack that is asleep and a stack that declines are both ordinary states
     * of the world, and a method answering with nothing could report them only
     * by throwing.
     */
    public function toHandOver(Stack $stack, Session $session): WhatTheyAreOwed;
}
