<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function checkdate;

use Closure;

/** The calendar day a title came out, where the core knows it. */
final readonly class WhenItWasReleased
{
    /** @param list{int, int, int}|null $day the year, the month and the day, where the core knows them */
    private function __construct(private ?array $day) {}

    /** A day the calendar has. */
    public static function on(int $year, int $month, int $day): self
    {
        if (! checkdate($month, $day, $year)) {
            throw ReleaseIsNoDay::onTheCalendar();
        }

        return new self([$year, $month, $day]);
    }

    public static function unstated(): self
    {
        return new self(null);
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
        return $this->day === null ? $unstated() : $on(...$this->day);
    }
}
