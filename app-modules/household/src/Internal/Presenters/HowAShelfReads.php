<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use function array_key_exists;
use function array_slice;

use Modules\Household\Internal\ViewModels\HowAPosterIsLettered;
use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToBeAbleToWatch;
use Modules\Household\Internal\ViewModels\WhatAShelfRowSays;
use Modules\Household\Internal\ViewModels\WhatOneHoldingSays;
use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Shelf;

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

    /** The core answered, and this is what it says they may watch. */
    public function these(Shelf $shelf): WhatAMemberTurnedOutToBeAbleToWatch
    {
        $posters = [];
        $byKind = [];

        foreach ($shelf as $holding) {
            $poster = $this->posterFor($holding);
            $posters[] = $poster;
            $byKind[$holding->medium()->value][] = $poster;
        }

        if ($posters === []) {
            return WhatAMemberTurnedOutToBeAbleToWatch::these([]);
        }

        $rows = [new WhatAShelfRowSays(self::NEW_IN_THE_HOUSE_IS_HEADED, array_slice($posters, 0, self::NEW_IN_THE_HOUSE))];

        foreach (Medium::cases() as $medium) {
            if (array_key_exists($medium->value, $byKind)) {
                $rows[] = new WhatAShelfRowSays($medium->shelvedUnder(), $byKind[$medium->value]);
            }
        }

        return WhatAMemberTurnedOutToBeAbleToWatch::these($rows);
    }

    /** The core answered and the library was not its to hand over. */
    public function outOfReach(Sentences $said): WhatAMemberTurnedOutToBeAbleToWatch
    {
        $reasons = [];

        foreach ($said as $sentence) {
            $reasons[] = $sentence->shown();
        }

        return WhatAMemberTurnedOutToBeAbleToWatch::outOfReach($reasons);
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

    /** One holding, as the poster it is drawn as. */
    private function posterFor(Holding $holding): WhatOneHoldingSays
    {
        $lettered = HowAPosterIsLettered::for($holding->titled());

        // Folded rather than tested, so the undated arm is one the type
        // insists on: a holding the core could not date is shown undated,
        // and a year invented here would be a fact about somebody's
        // library that nobody claimed.
        return $holding->year()->either(
            dated: static fn(int $year): WhatOneHoldingSays => new WhatOneHoldingSays(
                titled: $holding->titled(),
                medium: $holding->medium()->saidOnTheScreen(),
                year: (string) $year,
                lettered: $lettered,
            ),
            unstated: static fn(): WhatOneHoldingSays => new WhatOneHoldingSays(
                titled: $holding->titled(),
                medium: $holding->medium()->saidOnTheScreen(),
                year: '',
                lettered: $lettered,
            ),
        );
    }
}
