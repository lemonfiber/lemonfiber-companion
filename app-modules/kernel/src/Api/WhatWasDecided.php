<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The two things an operator can decide about a waiting request (`N2-R11`).
 *
 * A closed set, so an enum — `D4`. It is `WhatToDoWithIt`'s shape one screen
 * over, and for the same reason: the name this app shows and the name a stack
 * is asked by are two different words, and a screen sending its own would be
 * refused by a machine in front of somebody holding a phone.
 *
 * **Two and not three.** Leaving a request alone is not a decision — it is what
 * happens when nobody makes one — so there is no case for it. A third here
 * would be a thing an operator could send that the stack has nothing to do
 * with.
 */
enum WhatWasDecided: string
{
    /** The person who asked gets what they asked for. */
    case Approve = 'approve';

    /** They do not, and `D7-R7` says they are owed a sentence instead. */
    case Decline = 'decline';

    /**
     * What the stack calls it, which is the last segment of the action's path.
     *
     * Written out rather than taken from `value`, exactly as
     * {@see WhatToDoWithIt::asked()} is: the two vocabularies are allowed to
     * differ and a screen must not be the thing that notices when they do.
     */
    public function asked(): string
    {
        return match ($this) {
            self::Approve => 'household-approve',
            self::Decline => 'household-decline',
        };
    }
}
