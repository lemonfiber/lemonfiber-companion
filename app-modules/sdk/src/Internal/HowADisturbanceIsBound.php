<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/**
 * Which of a disturbance's two shapes one arrived in, as its `bound` tag says it.
 */
enum HowADisturbanceIsBound: string
{
    /** It has a clock on it: `seconds` is the longest it runs. */
    case Bounded = 'bounded';

    /** Nothing bounds it: `until` is what it waits for. */
    case OpenEnded = 'open-ended';
}
