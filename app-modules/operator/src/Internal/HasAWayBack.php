<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Native\Mobile\Edge\NativeComponent;

/**
 * A screen without a stack, which has a way back where it was opened on top of another.
 *
 * A screen about a stack has its back control beside the menu's. One without a
 * stack has no menu, so its top bar draws the platform's own back control, and
 * with it the edge swipe on iOS, wherever the router holds a screen beneath it.
 * At the bottom of the stack there is nothing to go back to and none is drawn.
 *
 * @phpstan-require-extends NativeComponent
 */
trait HasAWayBack
{
    /** Whether a screen lies beneath this one, which is when its top bar offers the way back. */
    public function hasAWayBack(): bool
    {
        return $this->nativeRouter?->isRootScreen() === false;
    }
}
