<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToHaveAsked;
use Modules\Household\Internal\ViewModels\WhatOneOfTheirRequestsSays;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\TurnedDown;
use Modules\Kernel\Api\Wanted;

/**
 * What asking a stack what a member asked for produces, as the fields a screen draws.
 *
 * The sibling of {@see HowWhatAMemberIsOwedReads}, and three answers to one
 * asking for its reason: the stack said something, the stack would not say, or
 * this device holds no session to ask with.
 *
 * **It writes no sentence of its own.** A title is the stack's, a refusal's
 * reason is the stack's, and where a request stands is a catalogue key off the
 * state that owns it. Nothing here composes a wording out of parts, which is what
 * keeps a member surface from becoming a second voice about a household's rules.
 *
 * **It says where a request stands in the member's words, not the operator's.**
 * {@see \Modules\Kernel\Api\Waiting::saidToTheMember()} rather than
 * `saidOnTheScreen()`: *waiting for your decision* is true to an operator and
 * false to the person who asked, and the enum draws that line once so no screen
 * has to remember which reader it is.
 */
final readonly class HowWhatAMemberAskedForReads
{
    /** The stack answered, and this is what they have asked for. */
    public function these(Requested $wanted): WhatAMemberTurnedOutToHaveAsked
    {
        $rows = [];

        foreach ($wanted as $one) {
            $rows[] = $this->row($one);
        }

        return WhatAMemberTurnedOutToHaveAsked::these($rows);
    }

    /**
     * It did not, and this is what the member met.
     *
     * The keys come off {@see Obstacle}, so an obstacle gaining another case
     * needs no edit here and cannot be given a sentence that disagrees with the
     * one the screen beside this shows.
     */
    public function met(Obstacle $why): WhatAMemberTurnedOutToHaveAsked
    {
        return WhatAMemberTurnedOutToHaveAsked::somethingStopped(
            $why->said(),
            $why->remedy(),
            $why->meansWeAreSignedOut(),
        );
    }

    /** This device holds no session for that stack, so nothing was asked. */
    public function signedOut(): WhatAMemberTurnedOutToHaveAsked
    {
        return WhatAMemberTurnedOutToHaveAsked::theSessionEnded();
    }

    /**
     * One request, as the three things a member reads about it.
     *
     * Built inside the arms rather than after them, the way the operator's own
     * row is: `refusal()` answers with an object so that a caller cannot read a
     * reason out without saying what happens when there was no refusal, and
     * building in each arm is what that shape is for.
     *
     * The reason is empty where there was no refusal, rather than absent: a
     * template reading a field that is sometimes there is a template with a
     * branch in it, and the empty string already says what an unrefused request
     * has to say about why it was refused.
     */
    private function row(Wanted $wanted): WhatOneOfTheirRequestsSays
    {
        $standing = $wanted->standing()->saidToTheMember();

        return $wanted->refusal(
            was: static fn(TurnedDown $why): WhatOneOfTheirRequestsSays
                => new WhatOneOfTheirRequestsSays($wanted->forWhat(), $standing, $why->reason()),
            wasNot: static fn(): WhatOneOfTheirRequestsSays
                => new WhatOneOfTheirRequestsSays($wanted->forWhat(), $standing, ''),
        );
    }
}
