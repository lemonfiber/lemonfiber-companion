<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The stacks this phone has begun removing and not yet finished.
 *
 * Written before anything of a stack is let go of and struck off once none of
 * it is kept, so a removal the app was stopped in the middle of is still here
 * when it opens again, and is finished then.
 */
interface RemovalsUnderWay
{
    /** Say that removing this stack has begun, before anything of it is let go of; whether it was written down. */
    public function begin(StackId $stack): bool;

    /** Every stack whose removal has begun and not been finished. */
    public function underWay(): StacksBeingRemoved;

    /** Strike this stack off, its removal finished; whether it was written down. */
    public function finished(StackId $stack): bool;
}
