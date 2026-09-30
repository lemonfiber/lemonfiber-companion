<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function preg_match;
use function trim;

/**
 * A count of days to keep readings for, as asked: one readings can be kept
 * for, or one they cannot.
 *
 * The one place that says which counts are allowed, whether the count was
 * typed on App settings or read back from what the phone kept.
 */
final readonly class DaysAsked
{
    private function __construct(private ?HowLongReadingsAreKept $kept) {}

    /** A count as the operator typed it: whole digits, nothing else, around any spaces. */
    public static function typed(string $typed): self
    {
        $digits = trim($typed);

        return preg_match('/\A\d+\z/', $digits) === 1 ? self::counted((int) $digits) : new self(null);
    }

    /** A count, allowed from {@see HowLongReadingsAreKept::FEWEST_DAYS} to {@see HowLongReadingsAreKept::MOST_DAYS}. */
    public static function counted(int $days): self
    {
        return $days >= HowLongReadingsAreKept::FEWEST_DAYS && $days <= HowLongReadingsAreKept::MOST_DAYS
            ? new self(HowLongReadingsAreKept::days($days))
            : new self(null);
    }

    /**
     * @template TAllowed of object
     * @template TRefused of object
     *
     * @param Closure(HowLongReadingsAreKept): TAllowed $allowed readings can be kept for this many days
     * @param Closure(): TRefused                       $refused they cannot
     *
     * @return TAllowed|TRefused
     */
    public function either(Closure $allowed, Closure $refused): object
    {
        return $this->kept instanceof HowLongReadingsAreKept ? $allowed($this->kept) : $refused();
    }
}
