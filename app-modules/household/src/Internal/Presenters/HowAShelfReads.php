<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use function array_key_exists;
use function array_slice;

use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToBeAbleToWatch;
use Modules\Household\Internal\ViewModels\WhatAShelfRowSays;
use Modules\Household\Internal\ViewModels\WhatOnePosterSays;
use Modules\Household\Internal\WhereTheHouseIs;
use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Shelf;
use Modules\Kernel\Api\StackId;

/**
 * A member's shelf, flattened into the rows of posters a template draws.
 *
 * Pure, like every presenter here: it reads what it was handed and reaches
 * nothing. What is on the shelf was decided by the core before this was called,
 * and nothing in here filters, sorts or hides a holding — a presenter that
 * dropped one would be a second place a member's library is decided.
 *
 * **Rows, in the core's order.** The first row is what came into the house
 * most recently, which is the core's own order: it answers newest first, so
 * the row is the front of the shelf as it came, cut at a length and not
 * re-sorted. A row follows for each kind the shelf holds, in the order the
 * kinds are declared, each in the core's order too. A kind the shelf holds
 * none of has no row: a heading over nothing reads as a row that did not
 * load. A holding is in two rows when it is new, since the first row is a way
 * in rather than a section of its own.
 */
final readonly class HowAShelfReads
{
    /** How many of the newest holdings the first row shows. */
    public const int NEW_IN_THE_HOUSE = 12;

    /** The key the first row is headed with. */
    private const string NEW_IN_THE_HOUSE_IS_HEADED = 'household.shelf.new';

    /**
     * The core answered, and this is what it says they may watch, each title
     * opening its own screen on this machine.
     */
    public function these(Shelf $shelf, StackId $opensOn): WhatAMemberTurnedOutToBeAbleToWatch
    {
        return $this->drawn($shelf, $opensOn);
    }

    /**
     * The core answered for the household's defaults, drawn as a member's Home
     * is and opening nothing: a preview is looked at, not used.
     */
    public function asAPreview(Shelf $shelf): WhatAMemberTurnedOutToBeAbleToWatch
    {
        return $this->drawn($shelf, null);
    }

    /** The core answered and the library was not its to hand over. */
    public function outOfReach(): WhatAMemberTurnedOutToBeAbleToWatch
    {
        return WhatAMemberTurnedOutToBeAbleToWatch::outOfReach();
    }

    /**
     * Something stood in the way, and this is what the member met.
     *
     * The keys come off {@see Obstacle}, so an obstacle gaining another case
     * needs no edit here and cannot be given a sentence that disagrees with
     * the screen beside this one.
     */
    public function met(Obstacle $why): WhatAMemberTurnedOutToBeAbleToWatch
    {
        return WhatAMemberTurnedOutToBeAbleToWatch::somethingStopped($why);
    }

    /** This device holds no session for that stack, so nothing was asked. */
    public function signedOut(): WhatAMemberTurnedOutToBeAbleToWatch
    {
        return WhatAMemberTurnedOutToBeAbleToWatch::theSessionEnded();
    }

    /**
     * The hero and the rows, from one pass over the shelf.
     *
     * The hero is the shelf's first title, which is the newest in the house by
     * the core's own order; nothing here weighs one title against another.
     */
    private function drawn(Shelf $shelf, ?StackId $opensOn): WhatAMemberTurnedOutToBeAbleToWatch
    {
        $posters = [];
        $byKind = [];

        foreach ($shelf as $holding) {
            $poster = $this->posterFor($holding, $opensOn);
            $posters[] = $poster;
            $byKind[$holding->medium()->value][] = $poster;
        }

        if ($posters === []) {
            return WhatAMemberTurnedOutToBeAbleToWatch::these(null, []);
        }

        $rows = [new WhatAShelfRowSays(self::NEW_IN_THE_HOUSE_IS_HEADED, array_slice($posters, 0, self::NEW_IN_THE_HOUSE))];

        foreach (Medium::cases() as $medium) {
            if (array_key_exists($medium->value, $byKind)) {
                $rows[] = new WhatAShelfRowSays($medium->shelvedUnder(), $byKind[$medium->value]);
            }
        }

        return WhatAMemberTurnedOutToBeAbleToWatch::these($posters[0], $rows);
    }

    /** One holding, as the poster it is drawn as. */
    private function posterFor(Holding $holding, ?StackId $opensOn): WhatOnePosterSays
    {
        // Folded rather than tested, so the undated arm is one the type
        // insists on: a holding the core could not date is shown undated,
        // and a year invented here would be a fact about somebody's
        // library that nobody claimed.
        return $holding->year()->either(
            dated: fn(int $year): WhatOnePosterSays => $this->dated($holding, (string) $year, $opensOn),
            unstated: fn(): WhatOnePosterSays => $this->dated($holding, '', $opensOn),
        );
    }

    /** The poster, once its year is said or known to be unsaid, opening its title where there is somewhere to open it on. */
    private function dated(Holding $holding, string $year, ?StackId $opensOn): WhatOnePosterSays
    {
        if (! $opensOn instanceof StackId) {
            return WhatOnePosterSays::ofATitle($holding->titled(), $holding->medium()->saidOnTheScreen(), $year);
        }

        return WhatOnePosterSays::ofATitle(
            titled: $holding->titled(),
            medium: $holding->medium()->saidOnTheScreen(),
            year: $year,
            goes: WhereTheHouseIs::of($opensOn)->title($holding->id()),
            plays: $holding->id()->named(),
        );
    }
}
