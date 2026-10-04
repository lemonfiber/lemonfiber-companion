<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use function max;

use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\ForgetsOldReadings;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\HowLongReadingsAreKept;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KeepsReadingsFor;
use Modules\Kernel\Api\SecondsIn;

/**
 * How long the phone keeps a stack's readings, and letting go of every one older than that.
 *
 * **One decision for every kind of reading.** How long is one of the phone's
 * settings, {@see HowLongReadingsAreKept::standard()} until the operator
 * chooses on App settings, and it is asked of every store of readings at once
 * through {@see ForgetsOldReadings}, which the composition root answers with
 * every store it registers: a kind of reading added tomorrow is let go of with
 * the rest the moment its store is registered.
 *
 * **When the app opens, and the moment the choice changes.** A reading read
 * longer ago than the length in force is let go of on the first frame past
 * the lock, and again as soon as the operator chooses a shorter time. Kept
 * until removed, nothing is let go of for its age.
 */
final readonly class LettingGoOfOldReadings
{
    public function __construct(
        private KeepsReadingsFor $period,
        private ForgetsOldReadings $readings,
        private Clock $clock,
    ) {}

    /** How long readings are kept, as the operator chose, or the standard. */
    public function keptFor(): HowLongReadingsAreKept
    {
        return $this->period->keptFor();
    }

    /**
     * Keep the operator's choice of how long, let go of every reading older
     * than it now, and answer what is in force.
     */
    public function keepFor(HowLongReadingsAreKept $kept): HowLongReadingsAreKept
    {
        $inForce = $this->period->keepFor($kept);
        $this->forgetOlderThan($inForce, $this->clock->now());

        return $inForce;
    }

    /** Let go of every reading kept longer than readings are kept for, as of now. */
    public function forgetTheOld(Instant $now): Forgotten
    {
        return $this->forgetOlderThan($this->keptFor(), $now);
    }

    /**
     * Let go of every reading older than that length allows, as of now.
     *
     * Never asking about a moment before the epoch: a clock reading less than
     * the length kept keeps everything.
     */
    private function forgetOlderThan(HowLongReadingsAreKept $kept, Instant $now): Forgotten
    {
        return $kept->either(
            days: fn(int $days): Forgotten => $this->readings->forgetOlderThan(
                Instant::atEpochSeconds(max(0, $now->epochSeconds() - $days * SecondsIn::ADay->value)),
            ),
            untilRemoved: static fn(): Forgotten => Forgotten::nothing(),
        );
    }
}
