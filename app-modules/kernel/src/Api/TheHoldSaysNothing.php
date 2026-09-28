<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A start the stack declined to run arrived with no reason for declining.
 *
 * Refused rather than shown: the reasons a start declines are ones an
 * operator acts on differently, and a declined start with a blank reason is
 * one they cannot act on at all.
 */
final class TheHoldSaysNothing extends InvalidArgumentException
{
    public static function whereAReasonWasOwed(): self
    {
        return new self('A start the stack declined arrived with no reason, and a declined start nobody can act on is one nobody should be shown.');
    }
}
