<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * What came back when a member's shelf was asked for.
 *
 * The same four answers every member-facing reading has — signed out, stopped,
 * told, and the one this screen adds: told, and the library was not the core's
 * to hand over.
 *
 * **Out of reach is not an empty shelf and the template cannot confuse them.**
 * Both would be drawn by the same `@forelse` and they say opposite things to
 * whoever is reading: one is *you have nothing here* and the other is *your
 * collection could not be reached*. Telling somebody the first when the second
 * is true is the failure this screen exists around.
 *
 * @param list<WhatAShelfRowSays> $rows
 */
use function array_key_exists;

use Closure;
use Modules\Kernel\Api\Obstacle;

final readonly class WhatAMemberTurnedOutToBeAbleToWatch
{
    /** How many posters at the front of each row are drawn with their pictures; the rest stay lettered, so a large library is never fetched whole. */
    public const int PICTURED_IN_EACH_ROW = 9;

    /** @param list<WhatAShelfRowSays> $rows */
    private function __construct(
        public bool $isSignedIn,
        public bool $isOutOfReach,
        public ?WhatOnePosterSays $hero,
        public array $rows,
        public ?WhatStoppedAMember $stopped = null,
    ) {}

    /**
     * The core answered, and this is the shelf: the title the hero draws, and
     * the rows.
     *
     * No rows and no hero is an ordinary answer: a member with nothing on
     * their shelf is told so in as many words.
     *
     * @param list<WhatAShelfRowSays> $rows
     */
    public static function these(?WhatOnePosterSays $hero, array $rows): self
    {
        return new self(
            isSignedIn: true,
            isOutOfReach: false,
            hero: $hero,
            rows: $rows,
        );
    }

    /**
     * The core answered and the library was not its to give.
     *
     * Said in the house's own sentence. Why it could not be read is the
     * core's finding for the operator, and a member is never shown one.
     */
    public static function outOfReach(): self
    {
        return new self(
            isSignedIn: true,
            isOutOfReach: true,
            hero: null,
            rows: [],
        );
    }

    /** This device no longer holds a session for that stack. */
    public static function theSessionEnded(): self
    {
        return new self(
            isSignedIn: false,
            isOutOfReach: false,
            hero: null,
            rows: [],
        );
    }

    /** Something stood in the way, and this is what the member met. */
    public static function somethingStopped(Obstacle $why): self
    {
        return $why->meansWeAreSignedOut()
            ? self::theSessionEnded()
            : new self(
                isSignedIn: true,
                isOutOfReach: false,
                hero: null,
                rows: [],
                stopped: WhatStoppedAMember::by($why),
            );
    }

    /**
     * The shelf, its hero drawn with its backdrop and the front of each row with their posters.
     *
     * Each poster is asked for once, however many rows it is in.
     *
     * @param Closure(string): ?string $poster   the source of the poster of the title a poster plays
     * @param Closure(string): ?string $backdrop the source of the backdrop of the title the hero plays
     */
    public function pictured(Closure $poster, Closure $backdrop): self
    {
        $sources = [];
        $once = static function (string $plays) use (&$sources, $poster): ?string {
            if (! array_key_exists($plays, $sources)) {
                $sources[$plays] = $poster($plays);
            }

            return $sources[$plays];
        };

        $rows = [];

        foreach ($this->rows as $row) {
            $rows[] = $row->pictured(self::PICTURED_IN_EACH_ROW, $once);
        }

        $hero = $this->hero;
        $plays = $hero?->plays;
        $hero = $hero instanceof WhatOnePosterSays && $plays !== null ? $hero->pictured($backdrop($plays)) : $hero;

        return new self($this->isSignedIn, $this->isOutOfReach, $hero, $rows, $this->stopped);
    }

    /** Whether there is a title to draw across the screen: an empty shelf has none. */
    public function hasAHero(): bool
    {
        return $this->hero instanceof WhatOnePosterSays;
    }

    /**
     * Whether the screen has a shelf to draw.
     *
     * The one expression of the rule, so the template asks rather than
     * restates it — and it is what keeps the empty arm of the list honest: a
     * `@forelse` reaches its `@empty` only inside this branch, so *there is
     * nothing on your shelf* is drawn where the library answered and never
     * where it could not be reached.
     */
    public function cameBack(): bool
    {
        return $this->isSignedIn && ! $this->stopped instanceof WhatStoppedAMember && ! $this->isOutOfReach;
    }
}
