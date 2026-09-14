<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

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
    private function __construct(
        private HowCurrent $how,
        private Releases $releases,
        private ?Release $running = null,
    ) {}

    /**
     * A reading where the stack did not say what it is on.
     *
     * Its own constructor rather than a null argument, which is `C2`'s cure and
     * {@see Daemon::thatExited()}'s shape: a screen handed a null would print
     * an empty version where one belongs, and an operator would read that as
     * *it is running nothing*.
     *
     * Reindexed rather than taken as it arrives: a variadic collected from
     * named arguments carries their names as keys, so being variadic is not
     * the same claim as being a list.
     */
    public static function reported(HowCurrent $how, Releases $releases): self
    {
        return new self($how, $releases);
    }

    /** The same reading, where the stack named the release in use. */
    public static function runningOn(HowCurrent $how, Release $running, Releases $releases): self
    {
        return new self($how, $releases, $running);
    }

    public function how(): HowCurrent
    {
        return $this->how;
    }

    /**
     * Say the release in use, or say the stack did not name one.
     *
     * Two arms rather than a nullable getter, for the reason
     * {@see Daemon::exit()} gives. A stack that has not looked is not a stack
     * running nothing, and `N2-R15` has this side report what it was told
     * rather than fill in a blank.
     *
     * @template TOn of object
     * @template TUnstated of object
     *
     * @param  Closure(Release): TOn  $on
     * @param  Closure(): TUnstated  $unstated
     * @return TOn|TUnstated
     */
    public function running(Closure $on, Closure $unstated): object
    {
        return $this->running instanceof Release ? $on($this->running) : $unstated();
    }

    /**
     * The releases worth offering, as the stack ordered them.
     *
     * A {@see Releases} rather than an array, which is `D1`, and filtered by
     * the collection rather than here so that `N2-R16`'s refusal lives in one
     * place.
     */
    public function waiting(): Releases
    {
        return $this->releases->worthOffering();
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
        return $this->how->hasSomethingWaiting() && ! $this->waiting()->isEmpty();
    }
}
