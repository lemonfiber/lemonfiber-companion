<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use Modules\Household\Internal\ViewModels\WhatAShelfRowSays;
use Modules\Household\Internal\ViewModels\WhatOnePosterSays;
use Modules\Household\Internal\ViewModels\WhatTheirOwnTitlesTurnedOutToBe;
use Modules\Household\Internal\WhereAnAskIsKept;
use Modules\Household\Internal\WhereTheHouseIs;
use Modules\Kernel\Api\KindOfRequest;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;

/**
 * A member's own requests, as the rows Home leads with, a music or book ask opening its own page: *Ready for you*, what
 * has arrived in whole or in part, and *On its way*, what is waiting for a
 * yes, being fetched or partly here.
 *
 * Pure, and it decides nothing about a request: which standing belongs to
 * which row is {@see Waiting}'s to say, the rows keep the core's order, and a
 * standing the core did not name is in neither, since nobody can say whether
 * it has arrived. A season partly here is in both. A row with nothing in it
 * is not made.
 */
final readonly class HowTheirOwnTitlesRead
{
    /** The key the row of what has arrived is headed with. */
    private const string READY_FOR_YOU = 'household.shelf.ready_for_you';

    /** The key the row of what is still coming is headed with. */
    private const string ON_ITS_WAY = 'household.shelf.on_its_way';

    /** The house answered, and this is what they asked for. */
    public function these(Requested $wanted, WhereTheHouseIs $house): WhatTheirOwnTitlesTurnedOutToBe
    {
        $ready = [];
        $coming = [];

        foreach ($wanted as $one) {
            $poster = $this->posterFor($one, $house);

            if ($one->standing()->isReadyForYou()) {
                $ready[] = $poster;
            }

            if ($one->standing()->isOnItsWay()) {
                $coming[] = $poster;
            }
        }

        $rows = [];

        if ($ready !== []) {
            $rows[] = new WhatAShelfRowSays(self::READY_FOR_YOU, $ready);
        }

        if ($coming !== []) {
            $rows[] = new WhatAShelfRowSays(self::ON_ITS_WAY, $coming);
        }

        return WhatTheirOwnTitlesTurnedOutToBe::these($rows);
    }

    /** Something stood in the way, and this is what the member met. */
    public function met(Obstacle $why): WhatTheirOwnTitlesTurnedOutToBe
    {
        return WhatTheirOwnTitlesTurnedOutToBe::somethingStopped($why);
    }

    /** This device holds no session for that house, so nothing was asked. */
    public function signedOut(): WhatTheirOwnTitlesTurnedOutToBe
    {
        return WhatTheirOwnTitlesTurnedOutToBe::theSessionEnded();
    }

    /**
     * One request, as the poster it is drawn as: where it stands, in their
     * words, above its name.
     *
     * Folded rather than read, so the unnamed arm is one the type insists
     * on: it says what the Requests tab says of a request whose standing
     * nobody named, though such a request is in neither row.
     */
    private function posterFor(Wanted $wanted, WhereTheHouseIs $house): WhatOnePosterSays
    {
        $standing = $wanted->standing()->either(
            said: static fn(Waiting $said): InWords => new InWords($said->saidToTheMember()),
            unnamed: static fn(): InWords => new InWords(HowWhatAMemberAskedForReads::UNNAMED),
        )->said;

        $kind = $wanted->called()->kind();
        $keptElsewhere = WhereAnAskIsKept::forThe($kind) !== '';

        $poster = $wanted->called()->either(
            named: static fn(string $title): WhatOnePosterSays => $keptElsewhere
                ? WhatOnePosterSays::ofAnAskKeptElsewhere($title, $standing, $kind->saidToTheMember())
                : WhatOnePosterSays::ofARequest($title, $standing),
            notYet: static fn(KindOfRequest $notYet): WhatOnePosterSays => WhatOnePosterSays::ofARequestNotYetNamed(HowWhatAMemberAskedForReads::notYetNamed($notYet), $standing),
        );

        return $keptElsewhere ? $poster->opening($house->asked($wanted->id())) : $poster;
    }
}
