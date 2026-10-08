<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use LogicException;

/**
 * Installing a plugin was agreed to against something that was not a reading anybody could agree to.
 *
 * A developer reads it: the screen offers Install only beneath a reading, so
 * reaching this is a caller that skipped one.
 */
final class PluginInstallWasNotRehearsed extends LogicException
{
    /** The answer agreed against carried no reading of an install. */
    public static function becauseNothingWasRead(): self
    {
        return new self('Installing a plugin was agreed to against an answer that was not a reading of an install, which is not an account anybody was shown before agreeing.');
    }
}
