<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_filter;
use function array_values;

/**
 * Where the stack stands on being up to date, as the stack reported it.
 *
 * One reading: how current it is, what it is running, and what is waiting. Held
 * together rather than fetched piecemeal because they are read from one payload
 * and answer one question — `N2-R15`'s answer is *current, pending or stale*,
 * and the release that makes it pending is part of the same sentence.
 *
 * **The waiting list is what the stack offered, filtered by what may be
 * offered.** `N2-R16` refuses a withdrawn release, and doing it here means a
 * screen cannot forget: what {@see waiting()} hands out is already only what is
 * worth offering, and {@see withdrawn()} is the separate question a screen asks
 * to tell somebody their stack is on one.
 */
final readonly class Upkeep
{
    /** @param list<Release> $releases */
    private function __construct(
        private HowCurrent $how,
        private ?Release $running,
        private array $releases,
    ) {}

    /**
     * What one reading of the stack's upkeep said.
     *
     * Reindexed rather than taken as it arrives: a variadic collected from
     * named arguments carries their names as keys, so being variadic is not
     * the same claim as being a list.
     */
    public static function reported(HowCurrent $how, ?Release $running, Release ...$releases): self
    {
        return new self($how, $running, array_values($releases));
    }

    public function how(): HowCurrent
    {
        return $this->how;
    }

    /**
     * The release in use, where the stack named one.
     *
     * Absent on a stack that has not looked, which is not the same as a stack
     * running nothing — and `N2-R15` has this side report what it was told
     * rather than fill in a blank.
     */
    public function running(): ?Release
    {
        return $this->running;
    }

    /**
     * The releases worth offering, newest as the stack ordered them.
     *
     * @return list<Release>
     */
    public function waiting(): array
    {
        return array_values(array_filter(
            $this->releases,
            static fn(Release $release): bool => $release->isWorthOffering(),
        ));
    }

    /**
     * Whether the release in use has been taken back.
     *
     * Asked apart from {@see waiting()} because it is the opposite errand: that
     * one is about what to take next, and this is about telling somebody the
     * ground they are standing on has moved.
     */
    public function runningAWithdrawnRelease(): bool
    {
        return $this->running instanceof Release && $this->running->wasWithdrawn();
    }

    /**
     * Whether there is an update to offer at all.
     *
     * Both halves, because either alone would be wrong: a stack that says
     * pending with every release withdrawn has nothing to offer, and a stack
     * that says current is not asked further (`N2-R20`).
     */
    public function hasSomethingToOffer(): bool
    {
        return $this->how->hasSomethingWaiting() && $this->waiting() !== [];
    }
}
