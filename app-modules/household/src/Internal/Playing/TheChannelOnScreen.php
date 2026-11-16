<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Playing;

use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PlaybackIs;

/**
 * What channel is on the player now, and how the last one stopped where it could not go on.
 *
 * One for the whole run, as {@see WhatIsPlaying} is, and held in memory and
 * nowhere else. A channel stops either on the player, which says why as a
 * {@see PlaybackIs}, or at its lapse, where why is what stood in the way of
 * renewing it.
 */
final class TheChannelOnScreen
{
    private ?AChannelOnScreen $now = null;

    private ?ChannelId $stoppedOn = null;

    private PlaybackIs $stoppedAs = PlaybackIs::Closed;

    private ?Obstacle $stoppedBy = null;

    /** A channel went on the player. */
    public function began(AChannelOnScreen $channel): void
    {
        $this->now = $channel;
        $this->stoppedOn = null;
        $this->stoppedBy = null;
    }

    /** What is on the player, or nothing. */
    public function now(): ?AChannelOnScreen
    {
        return $this->now;
    }

    /** The channel on the player, as renewing it last came to. */
    public function kept(AChannelOnScreen $channel): void
    {
        $this->now = $channel;
    }

    /** No channel is on the player any more. */
    public function over(): void
    {
        $this->now = null;
    }

    /** It stopped on the player where it could not go on. */
    public function stoppedOnThePlayer(PlaybackIs $as): void
    {
        $this->stop($as, null);
    }

    /** Its passes lapsed unrenewed, and this stood in the way. */
    public function lapsed(Obstacle $why): void
    {
        $this->stop(PlaybackIs::Closed, $why);
    }

    /** How the last channel tuned to here stopped on the player, or closed where it did not. */
    public function howItStoppedOn(ChannelId $channel): PlaybackIs
    {
        return $this->stoppedOn instanceof ChannelId && $this->stoppedOn->is($channel) ? $this->stoppedAs : PlaybackIs::Closed;
    }

    /** What stood in the way of renewing the last channel tuned to here, where it lapsed; null where it did not. */
    public function whyItLapsedOn(ChannelId $channel): ?Obstacle
    {
        return $this->stoppedOn instanceof ChannelId && $this->stoppedOn->is($channel) ? $this->stoppedBy : null;
    }

    private function stop(PlaybackIs $as, ?Obstacle $by): void
    {
        if ($this->now instanceof AChannelOnScreen) {
            $this->stoppedOn = $this->now->channel();
            $this->stoppedAs = $as;
            $this->stoppedBy = $by;
        }

        $this->now = null;
    }
}
