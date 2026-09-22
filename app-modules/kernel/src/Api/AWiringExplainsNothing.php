<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A service was wired to another by name, and nothing says why.
 *
 * Refused rather than carried. Wiring by name is not something the stack worked
 * out — it is an instruction somebody gave, and the reason is the only part of
 * it a later reader can evaluate. *Wired to qbittorrent, because: ▒* invites
 * the reading that the stack chose it, which is the one thing this arm exists
 * to say it did not.
 */
final class AWiringExplainsNothing extends InvalidArgumentException
{
    public static function whereAReasonWasExpected(): self
    {
        return new self(
            'A service was wired by name for no stated reason, and a name given without one cannot be told from a name the stack chose.',
        );
    }
}
