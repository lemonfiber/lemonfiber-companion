<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use Modules\Household\Internal\ViewModels\ALineAsShown;
use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToHaveAsked;
use Modules\Household\Internal\ViewModels\WhatOneOfTheirRequestsSays;
use Modules\Kernel\Api\KindOfRequest;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\TurnedDown;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;

use function sprintf;

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
 * {@see Waiting::saidToTheMember()} rather than
 * `saidOnTheScreen()`: *waiting for your decision* is true to an operator and
 * false to the person who asked, and the enum draws that line once so no screen
 * has to remember which reader it is.
 */
final readonly class HowWhatAMemberAskedForReads
{
    /** What a request is said to stand at where the core named no standing. */
    public const string UNNAMED = 'household.asked.unnamed';

    public const string NOT_YET_NAMED = 'household.not_yet_named.theirs.%s';

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
        return WhatAMemberTurnedOutToHaveAsked::somethingStopped($why);
    }

    /** This device holds no session for that stack, so nothing was asked. */
    public function signedOut(): WhatAMemberTurnedOutToHaveAsked
    {
        return WhatAMemberTurnedOutToHaveAsked::theSessionEnded();
    }

    /** The catalogue key for a request of theirs nothing has named yet, by its kind. */
    public static function notYetNamed(KindOfRequest $kind): string
    {
        return sprintf(self::NOT_YET_NAMED, $kind->value);
    }

    /**
     * One request, as the three things a member reads about it.
     *
     * Built inside the arms of what it is called, so a row holds the title or
     * the key for its kind and never both.
     *
     * The reason is empty where there was no refusal, rather than absent: a
     * template reading a field that is sometimes there is a template with a
     * branch in it, and the empty string already says what an unrefused request
     * has to say about why it was refused.
     */
    private function row(Wanted $wanted): WhatOneOfTheirRequestsSays
    {
        // The word a member is owed where the stack never named the standing.
        // It is a request of theirs and they can see it; what nobody can say is
        // where it has got to, and saying the nearest thing would be this app
        // telling them something lemonfiber declined to.
        return $wanted->standing()->either(
            said: fn(Waiting $said): WhatOneOfTheirRequestsSays
                => $this->told($wanted, $said->saidToTheMember()),
            unnamed: fn(): WhatOneOfTheirRequestsSays
                => $this->told($wanted, self::UNNAMED),
        );
    }

    /**
     * The row itself, once the standing has a word.
     *
     * Split from {@see self::row()} so the refusal is answered once rather than
     * once per standing — two independent questions written nested would be
     * four arms where there are two facts, which is the split the operator's
     * own row makes for the same reason.
     */
    private function told(Wanted $wanted, string $standing): WhatOneOfTheirRequestsSays
    {
        $reason = $wanted->refusal(was: static fn(TurnedDown $why): InWords => new InWords($why->reason()), wasNot: static fn(): InWords => new InWords(''))->said;

        return $wanted->called()->either(
            named: static fn(string $named): WhatOneOfTheirRequestsSays => new WhatOneOfTheirRequestsSays($named, $standing, $reason),
            notYet: static fn(KindOfRequest $kind): WhatOneOfTheirRequestsSays => new WhatOneOfTheirRequestsSays(new ALineAsShown(self::notYetNamed($kind), []), $standing, $reason),
        );
    }
}
