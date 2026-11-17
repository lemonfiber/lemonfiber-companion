<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Playing;

use Closure;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\WhyPlayingDidNotStart;

/**
 * What came of pressing a channel.
 *
 * The player came on screen; the core says it cannot be located, in its own
 * words, as away from home; something stood in the way of asking; the device
 * would not put the player on screen; or nobody is signed in to ask as.
 */
final readonly class WhatPressingAChannelCameTo
{
    private function __construct(private Sentence|Obstacle|WhyPlayingDidNotStart|null $why, private bool $isOnScreen) {}

    public static function onScreen(): self
    {
        return new self(null, isOnScreen: true);
    }

    public static function cannot(Sentence $why): self
    {
        return new self($why, isOnScreen: false);
    }

    public static function met(Obstacle $why): self
    {
        return new self($why, isOnScreen: false);
    }

    public static function notStarted(WhyPlayingDidNotStart $why): self
    {
        return new self($why, isOnScreen: false);
    }

    public static function signedOut(): self
    {
        return new self(null, isOnScreen: false);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TOnScreen of object
     * @template TCannot of object
     * @template TMet of object
     * @template TNotStarted of object
     * @template TSignedOut of object
     *
     * @param  Closure(): TOnScreen                        $onScreen
     * @param  Closure(Sentence): TCannot                  $cannot
     * @param  Closure(Obstacle): TMet                     $met
     * @param  Closure(WhyPlayingDidNotStart): TNotStarted $notStarted
     * @param  Closure(): TSignedOut                       $signedOut
     * @return TOnScreen|TCannot|TMet|TNotStarted|TSignedOut
     */
    public function either(Closure $onScreen, Closure $cannot, Closure $met, Closure $notStarted, Closure $signedOut): object
    {
        return match (true) {
            $this->isOnScreen => $onScreen(),
            $this->why instanceof Sentence => $cannot($this->why),
            $this->why instanceof Obstacle => $met($this->why),
            $this->why instanceof WhyPlayingDidNotStart => $notStarted($this->why),
            default => $signedOut(),
        };
    }
}
