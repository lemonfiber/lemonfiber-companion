<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A row of what stopped moving arrived that no operator could act on.
 *
 * Raised where the values become an {@see AStoppage}, for
 * {@see StuckSaysNothing}'s reason: these are values that cannot be built
 * rather than refusals crossing a boundary.
 */
final class StoppageSaysNothing extends InvalidArgumentException
{
    public static function whatStopped(): self
    {
        return new self('A stopped row arrived naming nothing, so a screen would ask somebody to act on a blank line.');
    }

    public static function howMany(int $items): self
    {
        return new self(sprintf('A stopped row said it stands for %d items, and a row stands for at least one.', $items));
    }
}
