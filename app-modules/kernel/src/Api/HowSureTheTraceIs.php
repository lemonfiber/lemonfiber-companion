<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * How sure a trace is that it followed the item asked for.
 *
 * A release renamed between services can only be matched fuzzily, and a guess
 * drawn as fact is worse than a marked one.
 */
enum HowSureTheTraceIs: string
{
    /** Joined on identifiers the services agree on. */
    case Certain = 'certain';

    /** Joined by fuzzy matching; the trace may not be the item asked for. */
    case Uncertain = 'uncertain';

    /** The catalogue key for the sentence drawn for it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.trace.confidence.%s', $this->value);
    }
}
