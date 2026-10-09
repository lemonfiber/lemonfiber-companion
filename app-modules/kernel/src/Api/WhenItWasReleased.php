<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function checkdate;

use Closure;

/** The calendar day a title came out, where the core knows it. */
final readonly class WhenItWasReleased
{
    private function __construct(private ?int $year, private int $month, private int $day) {}

    /** A day the calendar has. */
    public static function on(int $year, int $month, int $day): self
    {
        if (! checkdate($month, $day, $year)) {
            throw ReleaseIsNoDay::onTheCalendar();
        }

        return new self($year, $month, $day);
    }

    public static function unstated(): self
    {
        return new self(null, 0, 0);
    }

    /**
     * @template TOn of object
     * @template TUnstated of object
     *
     * @param Closure(int, int, int): TOn $on the year, the month and the day
     * @param Closure(): TUnstated        $unstated
     *
     * @return TOn|TUnstated
     */
    public function either(Closure $on, Closure $unstated): object
    {
        return $this->year === null ? $unstated() : $on($this->year, $this->month, $this->day);
    }
}
