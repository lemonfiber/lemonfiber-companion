<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * A screen that sent something to a stack and is following it to its end.
 *
 * Asked when the lock stands again over it: while it still awaits the outcome,
 * the device does not raise its prompt by itself, because a prompt raised over
 * an action in flight is answered to be rid of it rather than meant. Answered
 * from what the screen last heard, without asking the stack.
 */
interface AwaitsAnOutcome
{
    /** Whether the stack was still carrying out what this screen sent, when last heard. */
    public function awaitsAnOutcome(): bool;
}
