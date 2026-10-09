<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use Modules\Kernel\Api\Obstacle;

/**
 * One title's screen, as a template draws it.
 *
 * The answers every member reading has — signed out, stopped, told — and the
 * one a title adds: absent, which is how the core answers a title outside the
 * member's limits and one the household does not hold alike.
 */
final readonly class WhatThisTitleTurnedOutToBe
{
    private function __construct(
        public bool $isSignedIn,
        public bool $isAbsent,
        public string $met,
        public string $remedy,
        public ?WhatTheTitleSays $title,
        private ?Obstacle $why = null,
    ) {}

    /** The core answered with the title. */
    public static function told(WhatTheTitleSays $title): self
    {
        return new self(isSignedIn: true, isAbsent: false, met: '', remedy: '', title: $title);
    }

    /** The core says the title is not on this member's shelf. */
    public static function absent(): self
    {
        return new self(isSignedIn: true, isAbsent: true, met: '', remedy: '', title: null);
    }

    /** This device no longer holds a session for that stack. */
    public static function theSessionEnded(): self
    {
        return new self(isSignedIn: false, isAbsent: false, met: '', remedy: '', title: null);
    }

    /** Something stood in the way, and this is what the member met. */
    public static function somethingStopped(Obstacle $why): self
    {
        return $why->meansWeAreSignedOut()
            ? self::theSessionEnded()
            : new self(
                isSignedIn: true,
                isAbsent: false,
                met: WhatAMemberIsTold::met($why),
                remedy: WhatAMemberIsTold::remedy($why),
                title: null,
                why: $why,
            );
    }

    /** The name the screen is headed with, or nothing where there is no title. */
    public function named(): string
    {
        return $this->title instanceof WhatTheTitleSays ? $this->title->poster->titled : '';
    }

    /** Whether what stood in the way is put right on this app's page in the phone's settings. */
    public function isPutRightInTheAppsSettings(): bool
    {
        return $this->why instanceof Obstacle && $this->why->isPutRightInTheAppsSettings();
    }

    /**
     * What the obstacle's sentences are filled with, or nothing.
     *
     * @return array<string, int>
     */
    public function filling(): array
    {
        return $this->why instanceof Obstacle ? WhatAnObstacleNames::in($this->why) : [];
    }

    /** Whether {@see $met} and {@see $remedy} are the core's own text, drawn as written. */
    public function isInTheStacksWords(): bool
    {
        return $this->why instanceof Obstacle && WhatAMemberIsTold::isInTheStacksWords($this->why);
    }
}
