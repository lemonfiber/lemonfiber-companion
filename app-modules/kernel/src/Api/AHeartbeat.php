<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A heartbeat a monitor waits for: how often it should arrive, the grace allowed, and when it last did.
 */
final readonly class AHeartbeat
{
    private function __construct(private HowLong $every, private HowLong $grace, private WhenItSays $lastCheckIn) {}

    /** A heartbeat due every `$every`, missed once `$grace` has also passed since the last one. */
    public static function due(HowLong $every, HowLong $grace, WhenItSays $lastCheckIn): self
    {
        return new self($every, $grace, $lastCheckIn);
    }

    /** How often it should arrive. */
    public function every(): HowLong
    {
        return $this->every;
    }

    /** The grace allowed after it was due. */
    public function grace(): HowLong
    {
        return $this->grace;
    }

    /** When it last arrived, or that it never has. */
    public function lastCheckIn(): WhenItSays
    {
        return $this->lastCheckIn;
    }

    /** When it counts as missing: the last check-in, one period and the grace after it; unsaid where none ever arrived. */
    public function missingFrom(): WhenItSays
    {
        return $this->lastCheckIn->either(
            at: fn(Instant $last): WhenItSays => WhenItSays::at(Instant::atEpochSeconds($last->epochSeconds() + $this->every->inSeconds() + $this->grace->inSeconds())),
            unsaid: static fn(): WhenItSays => WhenItSays::unsaid(),
        );
    }
}
