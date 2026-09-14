<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A stalled item arrived without something an operator needs in order to act.
 *
 * Raised where the values become a {@see Stuck}, for {@see RepairSaysNothing}'s
 * reason: these are values that cannot be constructed rather than refusals
 * crossing a boundary, so there is nothing for a caller to open (`C1`, `C3`).
 *
 * Two named constructors rather than one taking a field name, for the same
 * reason as there: a stalled item nobody can name and a stalled item nothing
 * owns are different problems, and a message assembled from a field name is a
 * message nobody wrote.
 */
final class StuckSaysNothing extends InvalidArgumentException
{
    public static function itIsCalled(): self
    {
        return new self('A stalled item arrived with no title, so a screen listing it would put a blank row in front of somebody and ask them to act on it.');
    }

    public static function whichServiceHasIt(): self
    {
        return new self('A stalled item arrived naming no service, so nothing can say where an operator would go to look at it.');
    }
}
