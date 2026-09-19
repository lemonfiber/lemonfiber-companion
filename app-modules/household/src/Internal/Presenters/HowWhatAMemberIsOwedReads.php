<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToBeOwed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Sentences;

/**
 * What asking a stack what a member is owed produces, as the fields a screen draws.
 *
 * Three answers to one asking, none of them reached for: the stack said
 * something, the stack would not say, or this device holds no session to ask
 * with.
 *
 * **It writes no sentence of its own.** Everything a member reads here either
 * came off the wire in the core's words or is a catalogue key off the obstacle
 * that owns it. A presenter composing a wording from what it was handed would
 * be a second voice able to disagree with the core's about the household's own
 * rules — which is the one thing a member surface must never become.
 */
final readonly class HowWhatAMemberIsOwedReads
{
    /** The stack answered, and this is what it says they are owed. */
    public function these(Sentences $said): WhatAMemberTurnedOutToBeOwed
    {
        $lines = [];

        foreach ($said as $sentence) {
            $lines[] = $sentence->shown();
        }

        return WhatAMemberTurnedOutToBeOwed::these($lines);
    }

    /**
     * It did not, and this is what the member met.
     *
     * The keys come off {@see Obstacle}, so an obstacle gaining another case
     * needs no edit here and cannot be given a sentence here that disagrees
     * with the one the screen beside this shows.
     */
    public function met(Obstacle $why): WhatAMemberTurnedOutToBeOwed
    {
        return WhatAMemberTurnedOutToBeOwed::somethingStopped(
            $why->said(),
            $why->remedy(),
            $why->meansWeAreSignedOut(),
        );
    }

    /** This device holds no session for that stack, so nothing was asked. */
    public function signedOut(): WhatAMemberTurnedOutToBeOwed
    {
        return WhatAMemberTurnedOutToBeOwed::theSessionEnded();
    }
}
