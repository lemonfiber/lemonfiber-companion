<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_key_exists;

use DateInvalidTimeZoneException;
use DateTimeZone;

/**
 * Where on the planet a clock is set, by the name the time zone database gives it.
 *
 * The phone's own zone reaches this app through {@see LocalZone}, and this is
 * what it answers with: a name such as `Europe/Amsterdam`, which is enough to
 * say what the clock read at any moment — summer time included, because the
 * offset is looked up for the moment asked about rather than for today.
 *
 * **A name this runtime does not know reads as UTC.** The platform is the only
 * source of the name, and one that PHP's zone database cannot place is still a
 * clock somewhere; UTC is the reading that is wrong by a known, constant amount
 * rather than by an amount nobody can work out.
 */
final readonly class Zone
{
    /** The zone that is no zone: the offset every other one is counted from. */
    private const string UTC = 'UTC';

    private function __construct(private DateTimeZone $zone) {}

    /** The zone of that name, or UTC where the name places nothing. */
    public static function named(string $name): self
    {
        try {
            return new self(new DateTimeZone($name));
        } catch (DateInvalidTimeZoneException) {
            // The one thing a name can be wrong about, and the answer to it is
            // the zone every clock is counted from rather than no clock at all.
            return self::utc();
        }
    }

    /** Coordinated universal time, which is where a clock with no zone of its own is set. */
    public static function utc(): self
    {
        return new self(new DateTimeZone(self::UTC));
    }

    /** The name the zone database gives it. */
    public function name(): string
    {
        return $this->zone->getName();
    }

    /**
     * What a clock set here read at that moment.
     *
     * The offset in force at the moment itself, not the one in force now, so a
     * line written the evening before the clocks went back reads as it did on
     * that evening's clock.
     */
    public function timeOfDayAt(Instant $moment): TimeOfDay
    {
        $seconds = $moment->epochSeconds();

        return TimeOfDay::secondsIntoTheDay($seconds + $this->offsetAt($seconds));
    }

    /** How far this zone's clock stood from UTC at that second. */
    private function offsetAt(int $seconds): int
    {
        // The first transition answered is the one in force at the start of
        // the span asked about, and the span is that one second.
        $inForce = $this->zone->getTransitions($seconds, $seconds);

        return array_key_exists(0, $inForce) ? $inForce[0]['offset'] : 0;
    }
}
