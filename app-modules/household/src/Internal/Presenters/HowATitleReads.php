<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use function intdiv;

use Modules\Household\Internal\Month;
use Modules\Household\Internal\ViewModels\WhatOneEpisodeSays;
use Modules\Household\Internal\ViewModels\WhatOnePosterSays;
use Modules\Household\Internal\ViewModels\WhatOneSeasonSays;
use Modules\Household\Internal\ViewModels\WhatPlayingSays;
use Modules\Household\Internal\ViewModels\WhatTheTitleSays;
use Modules\Household\Internal\ViewModels\WhatThisTitleTurnedOutToBe;
use Modules\Kernel\Api\AnEpisode;
use Modules\Kernel\Api\ATitle;
use Modules\Kernel\Api\Genres;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowLongItRuns;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Seasons;
use Modules\Kernel\Api\SecondsIn;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\WhatPlayPlays;
use Modules\Kernel\Api\WhenItWasReleased;
use Modules\Kernel\Api\WhereItPlays;

/**
 * One title's screen, from the core's answer for it.
 *
 * Pure. Every part is the core's; a part it did not state is left empty, and
 * nothing here works one out. What the title's own Play plays is the title's
 * to say ({@see ATitle::whatPlayPlays()}).
 */
final readonly class HowATitleReads
{
    /** How long it runs, an hour or more. */
    private const string RUNS_HOURS = 'household.title.runs_hours';

    /** How long it runs, under an hour. */
    private const string RUNS_MINUTES = 'household.title.runs_minutes';

    /** When it came out. */
    private const string RELEASED = 'household.title.released';

    /** An episode's heading, with its number. */
    private const string EPISODE_NUMBERED = 'household.title.episode';

    /** An episode's heading, where it has no number. */
    private const string EPISODE_UNNUMBERED = 'household.title.episode_unnumbered';

    public function told(ATitle $title): WhatThisTitleTurnedOutToBe
    {
        $holding = $title->holding();
        $year = $holding->year()->either(
            dated: static fn(int $year): InWords => new InWords((string) $year),
            unstated: static fn(): InWords => new InWords(''),
        )->said;
        [$runs, $runsFilling] = $this->runs($title->runs());
        [$released, $releasedFilling, $releasedIn] = $this->released($title->released());

        return WhatThisTitleTurnedOutToBe::told(new WhatTheTitleSays(
            poster: WhatOnePosterSays::ofATitle($holding->titled(), $holding->medium()->saidOnTheScreen(), $year),
            about: $title->about(),
            runs: $runs,
            runsFilling: $runsFilling,
            genres: $this->genres($title->genres()),
            certificate: $title->certificate(),
            released: $released,
            releasedFilling: $releasedFilling,
            releasedIn: $releasedIn,
            playing: $this->playing($title->whatPlayPlays()),
            seasons: $this->seasons($title->seasons()),
        ));
    }

    public function absent(): WhatThisTitleTurnedOutToBe
    {
        return WhatThisTitleTurnedOutToBe::absent();
    }

    public function met(Obstacle $why): WhatThisTitleTurnedOutToBe
    {
        return WhatThisTitleTurnedOutToBe::somethingStopped($why);
    }

    public function signedOut(): WhatThisTitleTurnedOutToBe
    {
        return WhatThisTitleTurnedOutToBe::theSessionEnded();
    }

    /** Play for the title: whatever its own Play plays, or nothing to play. */
    private function playing(WhatPlayPlays $plays): WhatPlayingSays
    {
        return $plays->either(
            one: fn(HoldingId $id, string $titled, WhereItPlays $where): WhatPlayingSays => $this->playingWhere($where),
            nothing: static fn(): WhatPlayingSays => WhatPlayingSays::nothingPlays(),
        );
    }

    private function playingWhere(WhereItPlays $plays): WhatPlayingSays
    {
        return $plays->either(
            at: static fn(): WhatPlayingSays => WhatPlayingSays::plays(),
            cannot: static fn(Sentence $why): WhatPlayingSays => WhatPlayingSays::cannot($why->shown()),
            doesNotStream: static fn(): WhatPlayingSays => WhatPlayingSays::nothingPlays(),
        );
    }

    /** @return list<WhatOneSeasonSays> */
    private function seasons(Seasons $seasons): array
    {
        $drawn = [];

        foreach ($seasons as $season) {
            $episodes = [];

            foreach ($season->episodes() as $episode) {
                $episodes[] = $this->episode($episode);
            }

            $drawn[] = new WhatOneSeasonSays($season->named(), $episodes);
        }

        return $drawn;
    }

    private function episode(AnEpisode $episode): WhatOneEpisodeSays
    {
        [$runs, $runsFilling] = $this->runs($episode->runs());

        $titled = $episode->titled();
        [$headed, $headedFilling] = $episode->number()->either(
            number: static fn(int $number): Keyed => new Keyed(self::EPISODE_NUMBERED, ['number' => (string) $number, 'title' => $titled]),
            none: static fn(): Keyed => new Keyed(self::EPISODE_UNNUMBERED, ['title' => $titled]),
        )->asDrawn();

        return new WhatOneEpisodeSays(
            id: $episode->id()->named(),
            titled: $titled,
            headed: $headed,
            headedFilling: $headedFilling,
            runs: $runs,
            runsFilling: $runsFilling,
            about: $episode->about(),
            playing: $this->playingWhere($episode->plays()),
        );
    }

    /**
     * How long it runs, as a key and what fills it, or an empty key where unstated.
     *
     * @return array{string, array<string, int|string>}
     */
    private function runs(HowLongItRuns $runs): array
    {
        return $runs->either(
            minutes: static fn(int $minutes): Keyed => self::inHoursAndMinutes($minutes * SecondsIn::AMinute->value),
            unstated: static fn(): Keyed => new Keyed('', []),
        )->asDrawn();
    }

    /** A runtime in seconds, as hours and minutes where it runs an hour or more, and as minutes where it does not. */
    private static function inHoursAndMinutes(int $seconds): Keyed
    {
        $minutes = intdiv($seconds % SecondsIn::AnHour->value, SecondsIn::AMinute->value);

        return $seconds < SecondsIn::AnHour->value
            ? new Keyed(self::RUNS_MINUTES, ['minutes' => $minutes])
            : new Keyed(self::RUNS_HOURS, ['hours' => intdiv($seconds, SecondsIn::AnHour->value), 'minutes' => $minutes]);
    }

    /**
     * When it came out, as a key, its day and year, and its month's key; empty keys where unstated.
     *
     * @return array{string, array<string, int>, string}
     */
    private function released(WhenItWasReleased $released): array
    {
        return $released->either(
            on: static fn(int $year, int $month, int $day): ReleasedOn => new ReleasedOn(self::RELEASED, ['day' => $day, 'year' => $year], Month::numbered($month)->saidOnTheScreen()),
            unstated: static fn(): ReleasedOn => new ReleasedOn('', [], ''),
        )->asDrawn();
    }

    /**
     * Its genres, in the server's order.
     *
     * Collected by hand rather than with `iterator_to_array`, whose `preserve_keys` could not be wrong over a list.
     *
     * @return list<string>
     */
    private function genres(Genres $genres): array
    {
        $named = [];

        foreach ($genres as $genre) {
            $named[] = $genre;
        }

        return $named;
    }
}
