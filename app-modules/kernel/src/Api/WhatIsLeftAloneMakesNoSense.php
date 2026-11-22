<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/** What the stack said it was told to leave alone arrived with a part no declaration can have. */
final class WhatIsLeftAloneMakesNoSense extends InvalidArgumentException
{
    public static function saysNothingOf(string $part): self
    {
        return new self(sprintf('what the stack leaves alone arrived with no %s', $part));
    }

    public static function countsBelowNone(string $part): self
    {
        return new self(sprintf('what the stack leaves alone arrived with %s below none', $part));
    }
}
