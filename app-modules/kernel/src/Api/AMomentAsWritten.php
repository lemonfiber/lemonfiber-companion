<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function checkdate;

use Closure;

use function intdiv;
use function preg_match;

use const PREG_UNMATCHED_AS_NULL;

use function sprintf;

/**
 * A moment a service wrote down in the RFC 3339 form, read to the second.
 *
 * Log lines arrive stamped the way the engine stamps them —
 * `2026-09-29T21:39:01.530691525Z` — and that string is kept as written for
 * whoever wants it whole. This is the other reading of it: the {@see Instant}
 * it names, so a screen can say what the phone's own clock read at the time.
 *
 * **Worked out by arithmetic rather than with a date library**, which the kernel
 * keeps out: the days between the epoch and a calendar date are a formula, and
 * nothing about it depends on where the reader is standing.
 *
 * **Anything that is not such a moment reads as unreadable.** A service may
 * write whatever it likes where a timestamp would be, and a moment guessed from
 * half a timestamp is worse than a line with no time beside it. {@see read()}
 * is two arms rather than a nullable answer, so whoever reads one says what an
 * unreadable moment comes to.
 */
final readonly class AMomentAsWritten
{
    /** A moment written out as whole seconds since the epoch, in few enough digits to stay inside an integer. */
    public const string IN_SECONDS = '/\A\d{1,18}\z/';

    /**
     * A date, a `T`, a time to the second, any fraction, and `Z` or an offset.
     *
     * The fraction is read past and dropped: a time shown to the second has no
     * use for the nanoseconds the engine keeps.
     */
    private const string RFC_3339 = '/\A(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2})[Tt ](?<hour>\d{2}):(?<minute>\d{2}):(?<second>\d{2})(?:\.\d+)?(?:[Zz]|(?<sign>[+-])(?<hours>\d{2}):(?<minutes>\d{2}))\z/';

    /** The calendar repeats exactly every four hundred years, leap days and all. */
    private const int YEARS_IN_AN_ERA = 400;

    private const int DAYS_IN_A_YEAR = DaysIn::AYear->value;

    /** A leap year every fourth year… */
    private const int LEAP_EVERY = 4;

    /** …except every hundredth, which the era's own count puts back every four hundredth. */
    private const int NO_LEAP_EVERY = 100;

    /** The month the arithmetic counts a year from, so the leap day falls at its end. */
    private const int MARCH = 3;

    private const int MONTHS_IN_A_YEAR = 12;

    /**
     * Months from March come in runs of five that hold 153 days between them
     * (31, 30, 31, 30, 31), which is what lets a month's first day be worked
     * out rather than looked up.
     */
    private const int DAYS_IN_FIVE_MONTHS = 153;

    private const int FIVE_MONTHS = 5;

    private const int LAST_HOUR = 23;

    private const int LAST_MINUTE = 59;

    /** How an offset behind UTC is marked. */
    private const string BEHIND = '-';

    /** Sixty rather than fifty-nine: the standard allows a leap second. */
    private const int LAST_SECOND = 60;

    /** A date and a time to the second, with no zone: how lemonfiber writes an instant of its own, in UTC. */
    private const string THE_STACKS_OWN = '/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\z/';

    /** The zone the stack's own instants are written in, said the way RFC 3339 says it. */
    private const string IN_UTC = '%sZ';

    private const int DAYS_IN_AN_ERA = 146_097;

    /** From 1 March of year 0, where the era arithmetic starts, to 1 January 1970. */
    private const int DAYS_BEFORE_THE_EPOCH = 719_468;

    private function __construct(private ?Instant $moment) {}

    /** A timestamp as a service wrote it, read once, here. */
    public static function of(string $written): self
    {
        return new self(self::instantIn($written));
    }

    /** No moment at all, where nothing was written: it reads as unreadable. */
    public static function unwritten(): self
    {
        return new self(null);
    }

    /** A moment the stack wrote of its own, in UTC with no zone; anything else it reads as unreadable. */
    public static function byTheStack(string $written): self
    {
        return new self(preg_match(self::THE_STACKS_OWN, $written) === 1 ? self::instantIn(sprintf(self::IN_UTC, $written)) : null);
    }

    /** A moment written out as whole seconds since the epoch, as a link or a stack's record writes one; anything else it reads as unreadable. */
    public static function inSeconds(string $written): self
    {
        return new self(preg_match(self::IN_SECONDS, $written) === 1 ? Instant::atEpochSeconds((int) $written) : null);
    }

    /**
     * Say the moment the timestamp names, or that it names none.
     *
     * @template TRead of object
     * @template TUnreadable of object
     *
     * @param  Closure(Instant): TRead $read
     * @param  Closure(): TUnreadable  $unreadable
     * @return TRead|TUnreadable
     */
    public function read(Closure $read, Closure $unreadable): object
    {
        return $this->moment instanceof Instant ? $read($this->moment) : $unreadable();
    }

    /** The moment the timestamp names, where it names one. */
    private static function instantIn(string $written): ?Instant
    {
        if (preg_match(self::RFC_3339, $written, $part, PREG_UNMATCHED_AS_NULL) !== 1) {
            return null;
        }

        [$year, $month, $day] = [(int) $part['year'], (int) $part['month'], (int) $part['day']];
        [$hour, $minute, $second] = [(int) $part['hour'], (int) $part['minute'], (int) $part['second']];

        if (! checkdate($month, $day, $year) || $hour > self::LAST_HOUR || $minute > self::LAST_MINUTE || $second > self::LAST_SECOND) {
            return null;
        }

        $seconds = self::daysSinceTheEpoch($year, $month, $day) * SecondsIn::ADay->value
            + $hour * SecondsIn::AnHour->value
            + $minute * SecondsIn::AMinute->value
            + $second
            - self::offset($part);

        return $seconds < 0 ? null : Instant::atEpochSeconds($seconds);
    }

    /**
     * How far ahead of UTC the timestamp says its clock was, in seconds: nought for `Z`, which names no offset.
     *
     * @param array<array-key, string|null> $part
     */
    private static function offset(array $part): int
    {
        $ahead = (int) $part['hours'] * SecondsIn::AnHour->value + (int) $part['minutes'] * SecondsIn::AMinute->value;

        return $part['sign'] === self::BEHIND ? -$ahead : $ahead;
    }

    /**
     * The days from 1 January 1970 to a calendar date.
     *
     * Howard Hinnant's `days_from_civil`: the year is counted from March, so
     * the leap day falls at the end of it, and in eras of 400 years, which
     * repeat exactly. Only years from 1 on reach it, as no other passes
     * `checkdate()`, so the era arithmetic never meets a negative one.
     */
    private static function daysSinceTheEpoch(int $year, int $month, int $day): int
    {
        $marchYear = $month < self::MARCH ? $year - 1 : $year;
        $era = intdiv($marchYear, self::YEARS_IN_AN_ERA);
        $yearOfEra = $marchYear - $era * self::YEARS_IN_AN_ERA;
        $monthFromMarch = ($month - self::MARCH + self::MONTHS_IN_A_YEAR) % self::MONTHS_IN_A_YEAR;
        $dayOfYear = intdiv(self::DAYS_IN_FIVE_MONTHS * $monthFromMarch + 2, self::FIVE_MONTHS) + $day - 1;
        $dayOfEra = $yearOfEra * self::DAYS_IN_A_YEAR
            + intdiv($yearOfEra, self::LEAP_EVERY)
            - intdiv($yearOfEra, self::NO_LEAP_EVERY)
            + $dayOfYear;

        return $era * self::DAYS_IN_AN_ERA + $dayOfEra - self::DAYS_BEFORE_THE_EPOCH;
    }
}
