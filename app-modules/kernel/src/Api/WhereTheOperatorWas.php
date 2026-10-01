<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where the operator was: the stack last on view, and the tab last used on each.
 *
 * What the app opens on after the lock, and what a stack opens on when it is
 * chosen. A marker like the others the phone keeps, so Clear saved data lets go
 * of all of it and Remove from phone lets go of a stack's part.
 */
interface WhereTheOperatorWas extends ForgetsAStack, ForgetsEverythingKept
{
    /** Note that the operator is on this tab of this stack, and say whether it was kept. */
    public function wasOn(StackId $stack, WhichTab $tab): bool;

    /** Whether this is the stack the operator was last on. */
    public function wasLastOn(StackId $stack): bool;

    /** The tab the operator last used on this stack, and Health where they have used none. */
    public function tabOf(StackId $stack): WhichTab;
}
