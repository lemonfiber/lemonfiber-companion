<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/** A line a job said carried nothing to draw. */
final class AJobSaidNothing extends InvalidArgumentException
{
    public static function inALine(): self
    {
        return new self('A line a job said was blank or not text, so there is nothing to show of where the job has got.');
    }
}
