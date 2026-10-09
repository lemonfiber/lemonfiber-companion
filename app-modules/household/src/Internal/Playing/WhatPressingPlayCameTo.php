<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Playing;

use Closure;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhyPlayingDidNotStart;

/**
 * What came of pressing Play.
 *
 * The player came on screen; something stood in the way of asking the core;
 * the device would not play what the core stated; or the core's answer no
 * longer plays anything, and the page should read it again.
 */
final readonly class WhatPressingPlayCameTo
{
    private function __construct(private Obstacle|WhyPlayingDidNotStart|null $why, private bool $isOnScreen) {}

    /** The player is on screen. */
    public static function onScreen(): self
    {
        return new self(null, isOnScreen: true);
    }

    /** Asking the core for the title or for a grant met this. */
    public static function met(Obstacle $why): self
    {
        return new self($why, isOnScreen: false);
    }

    /** The device would not put the player on screen, and this is why. */
    public static function notStarted(WhyPlayingDidNotStart $why): self
    {
        return new self($why, isOnScreen: false);
    }

    /** The core's answer, asked again, plays nothing now. */
    public static function nothingToPlay(): self
    {
        return new self(null, isOnScreen: false);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TOnScreen of object
     * @template TMet of object
     * @template TNotStarted of object
     * @template TNothing of object
     *
     * @param  Closure(): TOnScreen  $onScreen
     * @param  Closure(Obstacle): TMet  $met
     * @param  Closure(WhyPlayingDidNotStart): TNotStarted  $notStarted
     * @param  Closure(): TNothing  $nothingToPlay
     * @return TOnScreen|TMet|TNotStarted|TNothing
     */
    public function either(Closure $onScreen, Closure $met, Closure $notStarted, Closure $nothingToPlay): object
    {
        return match (true) {
            $this->isOnScreen => $onScreen(),
            $this->why instanceof Obstacle => $met($this->why),
            $this->why instanceof WhyPlayingDidNotStart => $notStarted($this->why),
            default => $nothingToPlay(),
        };
    }
}
