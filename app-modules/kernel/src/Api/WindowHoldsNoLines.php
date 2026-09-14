<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A log window was asked for with no lines in it.
 *
 * Refused where the number becomes a {@see HowManyLines}, for
 * {@see RepairSaysNothing}'s reason: this is a value that cannot be constructed
 * rather than a refusal crossing a boundary, so there is nothing for a caller to
 * open (`C1`, `C3`).
 *
 * The figure is in the message because a bound that arrived as nought and one
 * that arrived as minus four came from different mistakes, and a developer
 * reading the log is the only audience — so it is `sprintf` and never
 * translated (`L1`).
 */
final class WindowHoldsNoLines extends InvalidArgumentException
{
    public static function of(int $lines): self
    {
        return new self(sprintf(
            'A log window of %d lines was asked for, and a read that asks for nothing has nothing to show for it. The fewest a window can be cut to is %d.',
            $lines,
            HowManyLines::AT_LEAST,
        ));
    }
}
