<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where a credential stands, as far as the stack can tell without spending it.
 *
 * Six cases, one per state the stack tells apart, and each is drawn in its own
 * words: *stale* is one to prove, *invalid* one to replace, and *rotating* is
 * mid-change rather than broken. A single warning for all three would throw
 * away the difference in what the operator does next.
 */
enum WhereACredentialStands: string
{
    /** Required by what the stack runs, and not supplied. */
    case Absent = 'absent';

    /** Present, and proven against the service it authenticates to. */
    case Active = 'active';

    /** Present, and not proven since it was last written. */
    case Stale = 'stale';

    /** Present, and refused by the service the last time it was offered. */
    case Invalid = 'invalid';

    /** A replacement is being proven; the existing value is still the one in force. */
    case Rotating = 'rotating';

    /** Replaced, and the old value is waiting to be destroyed. */
    case Superseded = 'superseded';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.credentials.state.%s', $this->value);
    }
}
