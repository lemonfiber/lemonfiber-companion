<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\Instant;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StandingsInMemory;

/** Everything a test of the line reaches for: the screen, and what stands in for its ports. */
final readonly class AScreenListening
{
    public function __construct(
        public HowThisStackIs $screen,
        public AStackThatSpeaksUp $stream,
        public FrozenClock $clock,
        public ACaptureInMemory $window,
        public AKeychainInMemory $keychain,
        public StandingsInMemory $standings,
    ) {}

    /** The screen wakes at a moment, as its poll would wake it. */
    public function wakesAt(int $seconds): self
    {
        $this->clock->moveTo(self::secondsAfterOpening($seconds));
        $this->screen->listen();

        return $this;
    }

    /** A moment, counted in seconds from one a test starts at. */
    public static function secondsAfterOpening(int $seconds): Instant
    {
        return Instant::atEpochSeconds(1_790_000_000 + $seconds);
    }
}
