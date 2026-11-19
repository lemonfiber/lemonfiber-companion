<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Operator\Internal\ViewModels\WhatSetupSettledAsShown;
use Native\Mobile\Edge\NativeComponent;

/**
 * A screen of a stack that also says what setup settled, read on a frame of its own.
 *
 * @phpstan-require-extends NativeComponent
 */
trait ReadsWhatSetupSettled
{
    use AsksWhatSetupSettled;

    /** What the screen drew of what setup settled, once a frame has read it. Public, as a screen's state is. */
    public ?WhatSetupSettledAsShown $settled = null;

    /** What setup settled, read on a frame of its own, or nothing while it waits. */
    public function settled(): ?WhatSetupSettledAsShown
    {
        if ($this->settled instanceof WhatSetupSettledAsShown || ! $this->mayReadItsStack()) {
            return $this->settled;
        }

        return $this->settled = $this->whatSetupSettled();
    }

    /** Let go of it, so the next frame reads it again. */
    private function forgetWhatSetupSettled(): void
    {
        $this->settled = null;
    }
}
