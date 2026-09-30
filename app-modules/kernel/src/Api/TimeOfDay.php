<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function intdiv;
use function sprintf;

/**
 * What a clock on the wall read at some moment: an hour, a minute and a second.
 *
 * No date and no zone. It is the answer to *what did the clock say*, asked of
 * a {@see Zone} about an {@see Instant}, and it carries nothing that would let
 * a screen mistake it for a moment it could compare or count from.
 */
final readonly class TimeOfDay
{
    private const int A_MINUTE = 60;

    private const int AN_HOUR = 3600;

    private const int A_DAY = 86_400;

    private function __construct(private int $secondsIntoTheDay) {}

    /**
     * The time a clock reads so many seconds past midnight.
     *
     * Any count is folded into one day, either way round, so a moment just
     * before midnight in a zone behind the one it was written in still reads
     * as the evening before rather than as a negative hour.
     */
    public static function secondsIntoTheDay(int $seconds): self
    {
        return new self((($seconds % self::A_DAY) + self::A_DAY) % self::A_DAY);
    }

    /**
     * The time as a 24-hour clock shows it, to the second: `21:39:01`.
     *
     * The same in every language this app speaks, which is why it is not a
     * word in the catalogue: a log line's time is read against the lines
     * around it, and a clock face does not translate.
     */
    public function shown(): string
    {
        return sprintf(
            '%02d:%02d:%02d',
            intdiv($this->secondsIntoTheDay, self::AN_HOUR),
            intdiv($this->secondsIntoTheDay % self::AN_HOUR, self::A_MINUTE),
            $this->secondsIntoTheDay % self::A_MINUTE,
        );
    }
}
