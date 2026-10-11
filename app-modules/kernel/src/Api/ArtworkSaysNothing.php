<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/** A picture arrived with no bytes, which is nothing to draw. */
final class ArtworkSaysNothing extends InvalidArgumentException
{
    public static function inItsBytes(): self
    {
        return new self('A picture arrived with no bytes, and there is nothing to draw.');
    }
}
