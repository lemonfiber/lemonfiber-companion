<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where somebody with an account on the media server stands, each in its own words.
 *
 * An invitation that lapsed and one that was turned down are both an account
 * nobody took up, and they are not the same answer: one was never acted on,
 * and the other was refused by the person it was for.
 */
enum WhereAMemberStands: string
{
    /** Invited, and the invitation is still open. */
    case Invited = 'invited';

    /** Invited, and the invitation lapsed before it was taken up. */
    case Lapsed = 'expired';

    /** Invited, and the invitee turned it down. */
    case Declined = 'declined';

    /** In the household. */
    case Active = 'active';

    /** In the household, with the account switched off. */
    case Suspended = 'suspended';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.invitation.member.%s', $this->value);
    }
}
