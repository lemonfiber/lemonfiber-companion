<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * How often a scheduled notification comes round.
 *
 * The PHP face of `Recurrence` in Kotlin and Swift, and it carries the same six
 * fields for the same reason: a call taking six loose integers is a call whose
 * arguments compile in any order, and the one that reads `weekday` where
 * `dayOfMonth` was meant produces an alert on a day nobody chose with nothing
 * on any screen to say so.
 *
 * **The day of the month stops at the 28th**, which the native halves enforce
 * and this one states. Every month has a 28th and no month has a 31st, and the
 * two platforms' calendars disagree about what to do with one — so a repeat an
 * operator cannot predict is refused rather than armed.
 */
final readonly class Repeat
{
    private function __construct(
        private HowOften $howOften,
        private int $hour,
        private int $minute,
        private int $weekday,
        private int $dayOfMonth,
        private int $month,
    ) {}

    /** Every hour, on a minute. */
    public static function hourly(int $minute): self
    {
        return new self(HowOften::Hourly, hour: 0, minute: $minute, weekday: 0, dayOfMonth: 1, month: 1);
    }

    /** Every day, at an hour and a minute. */
    public static function daily(int $hour, int $minute): self
    {
        return new self(HowOften::Daily, hour: $hour, minute: $minute, weekday: 0, dayOfMonth: 1, month: 1);
    }

    /** Every week, on a day counted from Sunday as zero. */
    public static function weekly(int $weekday, int $hour, int $minute): self
    {
        return new self(HowOften::Weekly, hour: $hour, minute: $minute, weekday: $weekday, dayOfMonth: 1, month: 1);
    }

    /** Every month, on a date. */
    public static function monthly(int $dayOfMonth, int $hour, int $minute): self
    {
        return new self(HowOften::Monthly, hour: $hour, minute: $minute, weekday: 0, dayOfMonth: $dayOfMonth, month: 1);
    }

    /** Every year, on a date in a month counted from one. */
    public static function yearly(int $month, int $dayOfMonth, int $hour, int $minute): self
    {
        return new self(
            HowOften::Yearly,
            hour: $hour,
            minute: $minute,
            weekday: 0,
            dayOfMonth: $dayOfMonth,
            month: $month,
        );
    }

    /**
     * This recurrence as the bridge carries it.
     *
     * @return array{frequency: string, hour: int, minute: int, weekday: int, dayOfMonth: int, month: int}
     */
    public function asAsked(): array
    {
        return [
            'frequency' => $this->howOften->value,
            'hour' => $this->hour,
            'minute' => $this->minute,
            'weekday' => $this->weekday,
            'dayOfMonth' => $this->dayOfMonth,
            'month' => $this->month,
        ];
    }
}
