<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Playing;

use Modules\Kernel\Api\ALiveStream;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Whose;

/**
 * One channel on the player: whose it is, on which stack, its live stream, and what renewing it last came to.
 *
 * Held in memory while it plays and nowhere else, because the stream's
 * addresses carry its pass. A refusal the core answered in its own words is
 * final: nothing renews after it, and the channel stops when the pass lapses.
 * A core that could not be reached is asked again on the next report, and
 * what stood in the way is kept for the lapse, should it come first.
 */
final readonly class AChannelOnScreen
{
    private function __construct(
        private Stack $stack,
        private Whose $whose,
        private ChannelId $channel,
        private ALiveStream $stream,
        private ?Obstacle $refused,
        private ?Obstacle $unreached,
    ) {}

    public static function of(Stack $stack, Whose $whose, ChannelId $channel, ALiveStream $stream): self
    {
        return new self($stack, $whose, $channel, $stream, null, null);
    }

    /** The same channel, its passes good until a later moment. */
    public function renewedUntil(Instant $lapses): self
    {
        return new self($this->stack, $this->whose, $this->channel, $this->stream->renewedUntil($lapses), null, null);
    }

    /** The same channel, which the core refused to renew, for good. */
    public function refused(Obstacle $why): self
    {
        return new self($this->stack, $this->whose, $this->channel, $this->stream, $why, null);
    }

    /** The same channel, whose renewal could not reach the core this time. */
    public function unreached(Obstacle $why): self
    {
        return new self($this->stack, $this->whose, $this->channel, $this->stream, null, $why);
    }

    public function stack(): Stack
    {
        return $this->stack;
    }

    public function whose(): Whose
    {
        return $this->whose;
    }

    public function channel(): ChannelId
    {
        return $this->channel;
    }

    public function stream(): ALiveStream
    {
        return $this->stream;
    }

    /** Whether the core refused to renew it, so nothing is asked again. */
    public function wasRefused(): bool
    {
        return $this->refused instanceof Obstacle;
    }

    /** Why it stops at the lapse: the core's refusal, or what stood in the way of the last renewal; null where nothing did. */
    public function whyItLapses(): ?Obstacle
    {
        return $this->refused ?? $this->unreached;
    }
}
