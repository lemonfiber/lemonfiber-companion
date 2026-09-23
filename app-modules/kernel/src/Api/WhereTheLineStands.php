<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where the line a stack shares with its household stands.
 *
 * Seven, and read as a closed set for {@see HowFarItGoesBack}'s reason: a
 * word this app has no case for is refused at the reading rather than drawn as
 * the nearest one, and the nearest one to an unknown state of somebody's line
 * is a claim about whether the house is being held back.
 */
enum WhereTheLineStands: string
{
    /** Nothing is configured. */
    case Unlimited = 'unlimited';

    /** Limits are in force, with no schedule to switch them. */
    case Limited = 'limited';

    /** Inside the household's active hours, so the limits apply. */
    case ScheduledActive = 'scheduled-active';

    /** Outside them, so the line is the stack's. */
    case ScheduledQuiet = 'scheduled-quiet';

    /** A temporary override is lifting the limits, and will expire. */
    case Overridden = 'overridden';

    /** The month is close enough to a declared cap to say so. */
    case CapWarning = 'cap-warning';

    /** The cap has been reached and the declared behaviour applies. */
    case CapExceeded = 'cap-exceeded';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.line.restraint.%s', $this->value);
    }
}
