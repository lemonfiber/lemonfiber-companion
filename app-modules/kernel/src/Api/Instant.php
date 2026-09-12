<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A moment, as a count of seconds since the epoch.
 *
 * Seconds rather than a `DateTimeImmutable` because `B1` keeps every date
 * library out of the kernel, and for a better reason than the rule: a date
 * object carries a timezone, and a timezone is a rendering decision. Nothing
 * the domain decides — whether a session has expired, whether a backup is
 * three days old — changes with where the reader is standing. The one place
 * that does change is the screen, and a screen is given the formatting it
 * needs rather than an object that has already made the choice.
 *
 * Second resolution, deliberately. The distinctions this application draws are
 * "expired", "stale" and "three days old"; a millisecond in the type would be
 * a precision nothing needs and a difference two adapters could disagree
 * about.
 */
final readonly class Instant
{
    private function __construct(private int $seconds) {}

    /**
     * The one place a number becomes a moment.
     *
     * Negative is refused. Every instant this application handles comes from a
     * clock or from a stack, and neither answers with a moment before 1970 —
     * so a negative one is a subtraction that went wrong somewhere upstream,
     * and carrying it would turn "expired" into "expired fifty years ago".
     */
    public static function atEpochSeconds(int $seconds): self
    {
        if ($seconds < 0) {
            throw InstantIsBeforeTheEpoch::of($seconds);
        }

        return new self($seconds);
    }

    public function epochSeconds(): int
    {
        return $this->seconds;
    }

    public function isBefore(self $other): bool
    {
        return $this->seconds < $other->seconds;
    }

    public function isAfter(self $other): bool
    {
        return $this->seconds > $other->seconds;
    }

    public function is(self $other): bool
    {
        return $this->seconds === $other->seconds;
    }
}
