<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use function array_keys;
use function count;
use function max;
use function min;

use Modules\Design\Api\ATap;
use Modules\Household\Internal\ViewModels\WhatACellSays;
use Modules\Household\Internal\ViewModels\WhatAGuideRowSays;
use Modules\Household\Internal\ViewModels\WhatAMarkSays;
use Modules\Household\Internal\ViewModels\WhatTheGuideTurnedOutToBe;
use Modules\Kernel\Api\AChannelsGuide;
use Modules\Kernel\Api\AnAiring;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SecondsIn;
use Modules\Kernel\Api\TheGuide;
use Modules\Kernel\Api\Zone;

use function round;
use function sprintf;
use function usort;

/**
 * The guide as a grid: the half hours along the top on the phone's own clock, and a row for each channel in the core's order.
 *
 * The grid starts at the half hour the clock is in now. Each programme is
 * drawn as wide as it runs, from the grid's start or its own, whichever is
 * later; the time between two programmes is empty space, which is how a
 * programme the core left out for the member's limits looks; and a row stops
 * where its guide ends. Nothing here widens, narrows or fills the window the
 * core answered.
 */
final readonly class HowTheGuideReads
{
    /** How wide a minute is drawn, in points. */
    public const int POINTS_PER_MINUTE = 4;

    /** What pressing a later programme calls on the screen. */
    public const string TELLS_ABOUT = 'aboutTheProgramme';

    private Instant $starts;

    public function __construct(private Zone $here, private Instant $now)
    {
        $this->starts = Instant::atEpochSeconds($now->epochSeconds() - $here->timeOfDayAt($now)->secondsInto(SecondsIn::AHalfHour));
    }

    public function these(TheGuide $guide): WhatTheGuideTurnedOutToBe
    {
        $rows = [];
        $ends = $this->starts;

        foreach ($guide as $channel) {
            $until = $this->endOf($channel);
            $ends = $until->isAfter($ends) ? $until : $ends;
            $rows[] = $this->row($channel, $until, count($rows));
        }

        return WhatTheGuideTurnedOutToBe::these($this->marks($ends), $rows);
    }

    /** Where a channel's row stops: where its guide ends, or its last programme does, whichever is later. */
    private function endOf(AChannelsGuide $channel): Instant
    {
        $ends = $channel->until()->either(
            at: static fn(Instant $at): Instant => $at,
            unsaid: fn(): Instant => $this->starts,
        );

        foreach ($channel->airings() as $airing) {
            $ends = $airing->ends()->isAfter($ends) ? $airing->ends() : $ends;
        }

        return $ends;
    }

    private function row(AChannelsGuide $channel, Instant $until, int $at): WhatAGuideRowSays
    {
        $airings = [];

        foreach ($channel->airings() as $airing) {
            $airings[] = $airing;
        }

        $order = array_keys($airings);
        usort($order, static fn(int $one, int $other): int => $airings[$one]->starts()->epochSeconds() <=> $airings[$other]->starts()->epochSeconds());

        $cells = [];
        $reached = $this->starts->epochSeconds();

        foreach ($order as $sent) {
            $airing = $airings[$sent];
            $from = max($airing->starts()->epochSeconds(), $reached);
            $to = min($airing->ends()->epochSeconds(), $until->epochSeconds());

            if ($to <= $from) {
                continue;
            }

            if ($from > $reached) {
                $cells[] = WhatACellSays::aGap($this->width($reached, $from));
            }

            $cells[] = $this->programme($channel, $airing, $this->width($from, $to), $at, $sent);
            $reached = $to;
        }

        return new WhatAGuideRowSays(
            id: $channel->id()->named(),
            name: $channel->name(),
            number: $channel->number(),
            shown: HowTheChannelsRead::shown($channel->number(), $channel->name()),
            tap: ATap::calling(HowTheChannelsRead::TUNES, $channel->id()->named())->written(),
            cells: $cells,
        );
    }

    private function programme(AChannelsGuide $channel, AnAiring $airing, int $width, int $row, int $sent): WhatACellSays
    {
        $isOnNow = $airing->isOnAt($this->now);

        return WhatACellSays::aProgramme(
            title: $airing->title(),
            times: $this->times($airing),
            certificate: $airing->certificate(),
            width: $width,
            tap: $isOnNow ? ATap::calling(HowTheChannelsRead::TUNES, $channel->id()->named())->written() : ATap::calling(self::TELLS_ABOUT, $row, $sent)->written(),
            isOnNow: $isOnNow,
            at: $sent,
        );
    }

    /** @return list<WhatAMarkSays> */
    private function marks(Instant $ends): array
    {
        $marks = [];
        $halfHour = SecondsIn::AHalfHour->value;

        for ($at = $this->starts->epochSeconds(); $at < $ends->epochSeconds(); $at += $halfHour) {
            $marks[] = new WhatAMarkSays($this->clock(Instant::atEpochSeconds($at)), $this->width($at, $at + $halfHour));
        }

        return $marks;
    }

    private function times(AnAiring $airing): string
    {
        return sprintf('%s–%s', $this->clock($airing->starts()), $this->clock($airing->ends()));
    }

    private function clock(Instant $at): string
    {
        return $this->here->timeOfDayAt($at)->shownToTheMinute();
    }

    /** How wide the time between two moments is drawn, each placed from the grid's start so widths never drift. */
    private function width(int $from, int $to): int
    {
        $starts = $this->starts->epochSeconds();

        return $this->drawnAt($to - $starts) - $this->drawnAt($from - $starts);
    }

    /** Where a moment so many seconds into the grid is drawn, in points from its left edge. */
    private function drawnAt(int $secondsIn): int
    {
        return (int) round($secondsIn * self::POINTS_PER_MINUTE / SecondsIn::AMinute->value);
    }
}
