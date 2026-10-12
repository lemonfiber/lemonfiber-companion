<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What became of one limit, in one direction, on one download client.
 */
enum WhatALimitCameTo: string
{
    case Unasked = 'unasked';

    case NothingToLimit = 'nothing-to-limit';

    case Holding = 'holding';

    case Ignored = 'ignored';

    case Overrunning = 'overrunning';

    /** Whether this is the client not keeping to what it was given, which a screen marks. */
    public function isAMiss(): bool
    {
        return $this === self::Ignored || $this === self::Overrunning;
    }

    /** The catalogue key a screen says it with. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.line_change.verdict.%s', $this->value);
    }
}
