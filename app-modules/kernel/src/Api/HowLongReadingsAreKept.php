<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function max;
use function min;

/**
 * How long the phone keeps a stack's readings before it lets them go.
 *
 * A count of days from {@see FEWEST_DAYS} to {@see MOST_DAYS}, or until they
 * are removed. {@see standard()} is thirty days, which is what the phone
 * keeps them for until the operator chooses on App settings.
 */
final readonly class HowLongReadingsAreKept
{
    /** The fewest days a reading can be asked to be kept for. */
    public const int FEWEST_DAYS = 1;

    /** The most days a reading can be asked to be kept for: a year. */
    public const int MOST_DAYS = KeptFor::OneYear->value;

    /** Written in place of a count of days where readings are kept until removed. */
    private const int UNTIL_REMOVED = 0;

    /** @param int $days how many days, or {@see UNTIL_REMOVED} */
    private function __construct(private int $days) {}

    /** How long readings are kept until the operator says otherwise. */
    public static function standard(): self
    {
        return self::for(KeptFor::ThirtyDays);
    }

    /** One of the lengths offered by name. */
    public static function for(KeptFor $kept): self
    {
        return new self($kept->value);
    }

    /** Kept until the operator removes them, and never let go of for their age. */
    public static function untilRemoved(): self
    {
        return new self(self::UNTIL_REMOVED);
    }

    /**
     * A count of days.
     *
     * Held to {@see FEWEST_DAYS} to {@see MOST_DAYS}: {@see DaysAsked} says
     * whether a count is one readings can be kept for, and a count past either
     * end is kept at it.
     */
    public static function days(int $days): self
    {
        return new self(max(self::FEWEST_DAYS, min(self::MOST_DAYS, $days)));
    }

    /** Whether readings are kept until they are removed. */
    public function isUntilRemoved(): bool
    {
        return $this->days === self::UNTIL_REMOVED;
    }

    /** Whether this is the length offered under that name. */
    public function is(KeptFor $kept): bool
    {
        return $this->days === $kept->value;
    }

    /**
     * @template TDays of object
     * @template TUntilRemoved of object
     *
     * @param Closure(int): TDays          $days         how many days readings are kept for
     * @param Closure(): TUntilRemoved     $untilRemoved readings are kept until they are removed
     *
     * @return TDays|TUntilRemoved
     */
    public function either(Closure $days, Closure $untilRemoved): object
    {
        return $this->isUntilRemoved() ? $untilRemoved() : $days($this->days);
    }
}
