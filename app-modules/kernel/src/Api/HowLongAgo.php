<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_find;
use function array_reverse;
use function intdiv;
use function sprintf;

/**
 * The unit an age is said in, and the only place the bands are decided.
 *
 * A value not read in this session carries when it was read, and
 * *when* has to become words somewhere. This is that somewhere — for every
 * screen rather than for one, because the requirement is about the app and a
 * second screen phrasing an age its own way is how two screens come to
 * disagree about what *recently* means.
 *
 * **Coarse on purpose.** Past a day, whether a reading is thirty-one or
 * thirty-two hours old changes nothing an operator does, and precision that
 * changes nothing is noise on a phone.
 *
 * **Declared finest first**, which {@see self::over()} reads by walking the cases
 * backwards: the coarsest unit an age fills at least one of is the one it is
 * said in. The order is the meaning, so moving a case is a failing test rather
 * than a screen that quietly starts counting in days.
 *
 * **One band table, not two.** The count is `intdiv` by the unit's own length,
 * so the unit and the number beside it cannot disagree — a second `match`
 * choosing the divisor would be one decision spelled twice, which is the shape
 * that drifts.
 */
enum HowLongAgo: string
{
    case Minutes = 'minutes';

    case Hours = 'hours';

    case Days = 'days';

    /**
     * The unit the time since a reading is said in.
     *
     * An age that fills no unit — under a minute — is said in minutes, which
     * with a count of zero is the catalogue's *just now*. A reading from the
     * future lands there too; see {@see self::secondsBetween()} for why.
     */
    public static function since(Instant $read, Instant $now): self
    {
        return self::over(self::secondsBetween($read, $now));
    }

    /**
     * The unit a span of this many seconds is said in.
     *
     * The same bands as an age, because *how long it has been stuck* and *how
     * long ago it was heard* are both a length of time an operator weighs, and
     * two tables would come to disagree about where an hour starts.
     * {@see HowLong} is what reads a span through this.
     */
    public static function over(int $seconds): self
    {
        $filled = array_find(
            array_reverse(self::cases()),
            static fn(self $unit): bool => $seconds >= $unit->seconds(),
        );

        return $filled ?? self::Minutes;
    }

    /** How long one of this unit is, which is what the bands are. */
    public function seconds(): int
    {
        return match ($this) {
            self::Minutes => SecondsIn::AMinute->value,
            self::Hours => SecondsIn::AnHour->value,
            self::Days => SecondsIn::ADay->value,
        };
    }

    /** How many of this unit have passed since a reading was taken, floored. */
    public function howManySince(Instant $read, Instant $now): int
    {
        return intdiv(self::secondsBetween($read, $now), $this->seconds());
    }

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, which is the shape every word in this app reaches
     * the catalogue by — see {@see Conclusion::saidOnTheScreen()} for the
     * argument. The line it names counts on the number beside it, because *a
     * minute ago* and *two minutes ago* are not the same sentence in either
     * language this app speaks.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.ago.%s', $this->value);
    }

    /** What a span in this unit is called on a screen, as a key: *for three hours*. */
    public function heldOnTheScreen(): string
    {
        return sprintf('health.held_for.%s', $this->value);
    }

    /**
     * How long ago a reading was taken, never less than nothing.
     *
     * A clock that disagrees with itself — a device whose own has moved
     * backwards, or a stack whose clock is ahead — produces a reading in the
     * future. This app knows such a reading is not old and does not know enough
     * to say anything else, so it is treated as taken now: *just now*, where
     * the raw subtraction would put *in three hours* on somebody's screen.
     *
     * **The moment is clamped, not the difference.** `max($now - $read, 0)`
     * says the same thing and says it with a number written into the method,
     * which `D6` refuses — and says it unobservably, because every band here
     * divides by at least sixty: clamping to nought, to one or to minus one
     * produces the same count, and no test can tell the three apart. Taking the
     * earlier of the two moments has no constant to be wrong about, and getting
     * the comparison backwards is a negative count a test can name.
     */
    private static function secondsBetween(Instant $read, Instant $now): int
    {
        $taken = $read->isAfter($now) ? $now : $read;

        return $now->epochSeconds() - $taken->epochSeconds();
    }
}
