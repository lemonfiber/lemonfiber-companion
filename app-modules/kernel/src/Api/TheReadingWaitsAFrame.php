<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use RuntimeException;

/**
 * The frame being drawn asked the stack what it serves, so what else it reads waits for the next frame.
 *
 * A frame reads a stack once. A stack this app holds nothing for is asked what
 * it serves before anything is sent, and that is the frame's one reading: the
 * reading that was about to be sent is not sent, and is taken on the next
 * frame, which is asked for at once.
 *
 * Raised through the screen rather than answered as an obstacle, because it is
 * not one and nothing on the screen should say so: what came back is never
 * held, so the next frame asks for it as if this one had not, and the frame
 * that was being drawn is drawn instead as the screen waiting for the stack
 * ({@see \Modules\Wayfinding\Api\Screens\WaitsAFrameForWhatTheStackServes}).
 */
final class TheReadingWaitsAFrame extends RuntimeException
{
    public static function becauseTheStackWasAskedWhatItServes(): self
    {
        return new self('This frame asked the stack what it serves, so the reading it was about to take waits for the next frame.');
    }
}
