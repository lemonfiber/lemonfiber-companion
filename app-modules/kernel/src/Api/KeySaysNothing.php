<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/** An integration key described with something blank, or a scope this app has no case for. */
final class KeySaysNothing extends InvalidArgumentException
{
    public static function about(string $what): self
    {
        return new self(sprintf('An integration key needs a %s that says something.', $what));
    }
}
