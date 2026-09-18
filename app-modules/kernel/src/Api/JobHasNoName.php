<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A stack acknowledged an action without saying what to call it.
 *
 * Raised where a string becomes a {@see Job}. Without the name there is
 * nothing to ask after, so the app is holding an action it knows happened and
 * cannot learn the outcome of — which is the one state there is no answer
 * for: it may not replay the action, because the stack received it, and it may
 * not present it as pending either.
 *
 * Refused rather than carried as an empty string for `C3`'s reason and
 * {@see CheckIsUnnamed}'s: a nameless handle spends its whole life looking like
 * a handle, and the screen that asks after it gets a refusal from the far end
 * rather than a fault from here.
 */
final class JobHasNoName extends InvalidArgumentException
{
    public static function inAnAcknowledgement(): self
    {
        return new self('A stack acknowledged an action and did not name the job, so there is nothing to ask after — and the action was received, so it must not be sent again.');
    }
}
