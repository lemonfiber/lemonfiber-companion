<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ACredentialGrant;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Operator\Internal\ViewModels\AGrantAsShown;

/**
 * One grant of a credential to a third-party plugin, as the row that draws it, dated against the frame's moment.
 *
 * One presenter for the credentials screen, a revoke's account and a plugin's
 * own list, so a grant reads the same wherever it is drawn.
 */
final readonly class HowAGrantReads
{
    /** The grant at that place among those the stack listed. */
    public function one(ACredentialGrant $grant, int $which, Instant $now): AGrantAsShown
    {
        return new AGrantAsShown(
            which: $which,
            plugin: $grant->plugin()->named(),
            credential: $grant->credential(),
            whose: $grant->whose()->named(),
            fills: $grant->fills(),
            approved: $grant->approved()->read(
                read: static fn(Instant $at): AgoAsShown => AgoAsShown::from(HowLongAgo::since($at, $now), $at, $now),
                unreadable: AgoAsShown::saysNothing(...),
            ),
        );
    }
}
