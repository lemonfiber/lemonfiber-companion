<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AMomentInItsHistory;
use Modules\Kernel\Api\AnEpisodeNotHereYet;
use Modules\Kernel\Api\ASeriesCounted;
use Modules\Kernel\Api\AStageItReached;
use Modules\Kernel\Api\HowMuchOfASeasonIsHere;
use Modules\Kernel\Api\HowSureTheTraceIs;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\WhatTheTraceFound;
use Modules\Kernel\Api\WhereItGotTo;
use Modules\Operator\Internal\ViewModels\AMomentAsShown;
use Modules\Operator\Internal\ViewModels\AnEpisodeAsShown;
use Modules\Operator\Internal\ViewModels\ASeasonAsShown;
use Modules\Operator\Internal\ViewModels\ASeriesAsShown;
use Modules\Operator\Internal\ViewModels\AStageAsShown;
use Modules\Operator\Internal\ViewModels\HowMuchIsHereAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheTraceTurnedOutToBe;

/**
 * What following one item produces, as the fields a screen draws.
 *
 * `F2`: data in, view model out. A stage is carried as the stack's own word
 * with the plain sentence beside it, never one in place of the other.
 */
final readonly class HowATraceReads
{
    /** Nothing has been named to follow yet, so nothing was asked. */
    public function nothingToFollow(): TheTraceTurnedOutToBe
    {
        return self::nothingFrom(HowTheReadingWent::itCameBack(), '');
    }

    /** This device no longer holds a session for that stack. */
    public function signedOut(string $item): TheTraceTurnedOutToBe
    {
        return self::nothingFrom(HowTheReadingWent::theSessionEnded(), $item);
    }

    /** It could not be followed, and this is what the operator met. */
    public function met(Obstacle $why, string $item): TheTraceTurnedOutToBe
    {
        return self::nothingFrom(HowTheReadingWent::somethingStopped($why), $item);
    }

    /** The stack answered, and this is where the item got to. */
    public function this(WhereItGotTo $trace): TheTraceTurnedOutToBe
    {
        return $trace->either(
            nothingAskedFor: static fn(): TheTraceTurnedOutToBe => self::nothingFrom(HowTheReadingWent::itCameBack(), $trace->item()),
            followed: static fn(WhatTheTraceFound $found): TheTraceTurnedOutToBe => self::followed($trace->item(), $found),
        );
    }

    private static function followed(string $item, WhatTheTraceFound $found): TheTraceTurnedOutToBe
    {
        $stages = [];

        foreach ($found->got()->stages() as $stage) {
            $stages[] = self::stage($stage);
        }

        $history = [];

        foreach ($found->history() as $moment) {
            $history[] = self::moment($moment);
        }

        $disagreements = [];

        foreach ($found->disagreements() as $finding) {
            $disagreements[] = $finding;
        }

        return new TheTraceTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            item: $item,
            followed: true,
            sureSaid: $found->sure()->saidOnTheScreen(),
            isUncertain: $found->sure() === HowSureTheTraceIs::Uncertain,
            furthest: self::reached($found->got()->furthest(), '', ''),
            stall: $found->got()->stall(),
            stages: $stages,
            history: $history,
            disagreements: $disagreements,
            here: $found->here()->either(
                whole: static fn(): HowMuchIsHereAsShown => new HowMuchIsHereAsShown(null),
                inParts: static fn(ASeriesCounted $series): HowMuchIsHereAsShown => new HowMuchIsHereAsShown(self::series($series)),
            ),
        );
    }

    private static function stage(AStageItReached $stage): AStageAsShown
    {
        return self::reached($stage->stage(), $stage->service()->named(), $stage->at());
    }

    private static function reached(Stage $stage, string $service, string $at): AStageAsShown
    {
        return new AStageAsShown($stage->value, $stage->saidOnTheScreen(), $service, $at);
    }

    private static function moment(AMomentInItsHistory $moment): AMomentAsShown
    {
        return new AMomentAsShown($moment->happened()->saidOnTheScreen(), $moment->at());
    }

    private static function series(ASeriesCounted $series): ASeriesAsShown
    {
        $seasons = [];

        foreach ($series->seasons() as $season) {
            $seasons[] = self::season($season);
        }

        return new ASeriesAsShown($series->have(), $series->wanted(), $series->unmonitored(), $seasons);
    }

    private static function season(HowMuchOfASeasonIsHere $season): ASeasonAsShown
    {
        $outstanding = [];

        foreach ($season->outstanding() as $episode) {
            $outstanding[] = self::episode($episode);
        }

        return new ASeasonAsShown($season->season(), $season->have(), $season->wanted(), $season->unmonitored(), $outstanding);
    }

    private static function episode(AnEpisodeNotHereYet $episode): AnEpisodeAsShown
    {
        return new AnEpisodeAsShown(
            $episode->season(),
            $episode->number(),
            $episode->title(),
            $episode->stage()->value,
            $episode->stage()->saidOnTheScreen(),
        );
    }

    /** An answer with nothing followed in it. */
    private static function nothingFrom(HowTheReadingWent $went, string $item): TheTraceTurnedOutToBe
    {
        return new TheTraceTurnedOutToBe(
            went: $went,
            item: $item,
            followed: false,
            sureSaid: '',
            isUncertain: false,
            furthest: null,
            stall: '',
            stages: [],
            history: [],
            disagreements: [],
            here: new HowMuchIsHereAsShown(null),
        );
    }
}
