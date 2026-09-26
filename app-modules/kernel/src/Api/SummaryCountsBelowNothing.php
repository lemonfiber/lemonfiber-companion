<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

final class SummaryCountsBelowNothing extends InvalidArgumentException
{
    public static function wanting(int $said): self
    {
        return new self(sprintf(
            'A health summary said %d things want attention. A count below nothing is not a number of things, and showing it would put a sentence on the screen that nobody could act on.',
            $said,
        ));
    }
}
