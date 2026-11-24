<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Native\Mobile\Edge\NativeComponent;

/**
 * A screen whose job is asked after on the wake before a frame, counting that ask as the frame's one reading of the stack.
 *
 * @phpstan-require-extends NativeComponent
 */
trait CountsTheAskOnItsWake
{
    /** Whether the wake before this frame asked after the job. */
    private bool $theWakeAsked = false;

    abstract private function aFrameBegins(): void;

    abstract private function readsItsStack(): void;

    /** Note that this wake asks after the job. */
    private function theWakeAsks(): void
    {
        $this->theWakeAsked = true;
    }

    /** Begin a frame, counting the ask the wake before it took as the frame's one reading. */
    private function aFrameBeginsAfterItsWake(): void
    {
        $this->aFrameBegins();

        if ($this->theWakeAsked) {
            $this->readsItsStack();
        }

        $this->theWakeAsked = false;
    }
}
