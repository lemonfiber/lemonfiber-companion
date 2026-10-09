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
use Modules\Kernel\Api\Obstacle;

final readonly class WhatAMemberTurnedOutToBeAbleToWatch
{
    /** @param list<WhatAShelfRowSays> $rows */
    private function __construct(
        public bool $isSignedIn,
        public string $met,
        public string $remedy,
        public bool $isOutOfReach,
        public ?WhatOnePosterSays $hero,
        public array $rows,
        private ?Obstacle $why = null,
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
            met: '',
            remedy: '',
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
            met: '',
            remedy: '',
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
            met: '',
            remedy: '',
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
                met: WhatAMemberIsTold::met($why),
                remedy: WhatAMemberIsTold::remedy($why),
                isOutOfReach: false,
                hero: null,
                rows: [],
                why: $why,
            );
    }

    /** Whether what stood in the way is put right on this app's page in the phone's settings. */
    public function isPutRightInTheAppsSettings(): bool
    {
        return $this->why instanceof Obstacle && $this->why->isPutRightInTheAppsSettings();
    }

    /**
     * What the obstacle's sentences are filled with: the facts it was met with, or nothing.
     *
     * @return array<string, int>
     */
    public function filling(): array
    {
        return $this->why instanceof Obstacle ? WhatAnObstacleNames::in($this->why) : [];
    }

    /** Whether {@see $met} and {@see $remedy} are the core's own text, drawn as written, rather than catalogue keys. */
    public function isInTheStacksWords(): bool
    {
        return $this->why instanceof Obstacle && WhatAMemberIsTold::isInTheStacksWords($this->why);
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
        return $this->isSignedIn && $this->met === '' && ! $this->isOutOfReach;
    }
}
