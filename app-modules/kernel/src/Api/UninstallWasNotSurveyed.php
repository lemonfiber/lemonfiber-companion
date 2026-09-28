<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use LogicException;

/**
 * Taking lemonfiber off was agreed to against something that was not a reading anybody could agree to.
 *
 * A developer reads it: the screen offers the yes only beneath a reading,
 * and only once a data location on a network share or a drive that unplugs
 * has been acknowledged, so reaching this is a caller that skipped one.
 */
final class UninstallWasNotSurveyed extends LogicException
{
    /** The answer agreed against was not a reading, but a removal or a rehearsal of one. */
    public static function becauseItWasNotAReading(): self
    {
        return new self('Taking lemonfiber off was agreed to against an answer that was not a reading, which is not a list anybody was shown before agreeing.');
    }

    /** The reading named a data location on a volume that was not acknowledged apart. */
    public static function becauseTheVolumeWasNotAcknowledged(): self
    {
        return new self('Taking lemonfiber off was agreed to without the data location\'s volume being acknowledged, which the reading said before anything was agreed.');
    }
}
