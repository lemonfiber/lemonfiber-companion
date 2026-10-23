<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\WakingWhileAway;

/**
 * A platform that remembers whether it was asked to wake the app.
 *
 * Held to the same contract as the platform's in `WakingWhileAwayContractTest`.
 */
final class WakesInMemory implements WakingWhileAway
{
    private bool $asked = false;

    private function __construct(private readonly bool $takes) {}

    /** A platform that takes the request. */
    public static function thatWake(): self
    {
        return new self(takes: true);
    }

    /** A platform with background refresh turned off, which takes nothing. */
    public static function thatNeverWake(): self
    {
        return new self(takes: false);
    }

    public function ask(): bool
    {
        $this->asked = $this->takes;

        return $this->takes;
    }

    public function stop(): bool
    {
        $this->asked = false;

        return true;
    }

    /** Whether a wake is asked for now. */
    public function isAsked(): bool
    {
        return $this->asked;
    }
}
