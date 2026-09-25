<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheMembers;
use Modules\Operator\Internal\ViewModels\AMemberAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhoIsInTurnedOutToBe;

/**
 * Who is in the household, as the rows a screen draws.
 *
 * `F2`: data in, view model out.
 */
final readonly class HowWhoIsInReads
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): WhoIsInTurnedOutToBe
    {
        return new WhoIsInTurnedOutToBe(HowTheReadingWent::theSessionEnded(), []);
    }

    /** The stack answered, and these are its members. */
    public function these(TheMembers $members): WhoIsInTurnedOutToBe
    {
        $rows = [];

        foreach ($members as $member) {
            $rows[] = new AMemberAsShown(
                name: $member->name(),
                standingSaid: $member->hasJoined() ? 'stacks.invitation.member.joined' : 'stacks.invitation.member.still_invited',
            );
        }

        return new WhoIsInTurnedOutToBe(HowTheReadingWent::itCameBack(), $rows);
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): WhoIsInTurnedOutToBe
    {
        return new WhoIsInTurnedOutToBe(HowTheReadingWent::somethingStopped($why), []);
    }
}
