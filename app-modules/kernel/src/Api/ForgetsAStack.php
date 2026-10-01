<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Something the phone keeps for one stack, asked to let go of all of it.
 *
 * Removing a stack from the phone asks every keeper of it, the pairing and
 * the session among them, and then asks each whether it still keeps anything:
 * a removal is over only when none does. A keeper that cannot tell says that
 * it still keeps something, so a removal it could not finish is finished later
 * rather than taken as done.
 */
interface ForgetsAStack
{
    /** Let go of everything this keeps for the stack, and say how much that was. */
    public function forgetTheStack(StackId $stack): Forgotten;

    /** Whether this still keeps anything for the stack, or cannot tell. */
    public function keepsAnythingOf(StackId $stack): bool;
}
