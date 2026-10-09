<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Playing;

use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\PlaybackIs;

/**
 * What is on the player now, and how the last title stopped where it could not go on.
 *
 * One for the whole run, because the player plays on over whichever screen is
 * under it. Held in memory and nowhere else: a run that ends forgets it, and
 * the member's place is the core's to keep, not this.
 */
final class WhatIsPlaying
{
    private ?APlayback $now = null;

    private ?HoldingId $stoppedOn = null;

    private PlaybackIs $stoppedAs = PlaybackIs::Closed;

    /** A title went on the player. */
    public function began(APlayback $playback): void
    {
        $this->now = $playback;
        $this->stoppedOn = null;
    }

    /** What is on the player, or nothing. */
    public function now(): ?APlayback
    {
        return $this->now;
    }

    /** Nothing is on the player any more. */
    public function over(): void
    {
        $this->now = null;
    }

    /** It stopped where it could not go on, and the page it was played from says why. */
    public function stopped(PlaybackIs $as): void
    {
        if ($this->now instanceof APlayback) {
            $this->stoppedOn = $this->now->page();
            $this->stoppedAs = $as;
        }

        $this->now = null;
    }

    /** How the last title played from this page stopped where it could not go on, or closed where it did not. */
    public function howItStoppedOn(HoldingId $page): PlaybackIs
    {
        return $this->stoppedOn instanceof HoldingId && $this->stoppedOn->named() === $page->named()
            ? $this->stoppedAs
            : PlaybackIs::Closed;
    }
}
