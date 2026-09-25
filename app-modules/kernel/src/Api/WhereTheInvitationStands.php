<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What the stack found where an invitation was going.
 *
 * Four answers and none of them a failure. Each is a different thing to say
 * to the person: an account made, an invitation already out, somebody who is
 * already in, or a password taken off an account that was already theirs.
 */
enum WhereTheInvitationStands: string
{
    /** The account did not exist, and was made. */
    case Made = 'made';

    /** An invitation was already out for them, and it still stands. */
    case Waiting = 'waiting';

    /** They have set a password, so they are already in the household. */
    case Joined = 'joined';

    /** The account was theirs already, and its password has been taken off. */
    case Reset = 'reset';

    /**
     * Whether there is an address for them to use.
     *
     * Everything but *joined*: somebody who is already in needs nothing
     * handed over, and a link sent to them reads as an invitation to an
     * account they have been using all along.
     */
    public function leavesSomethingToHandOver(): bool
    {
        return $this !== self::Joined;
    }

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.invitation.standing.%s', $this->value);
    }
}
