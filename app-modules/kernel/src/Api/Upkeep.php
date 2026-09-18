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
 * worth offering, and {@see runningAWithdrawnRelease()} is the separate
 * question a screen asks to tell somebody their stack is on one.
 *
 * **What it is standing on and what it could take are two types.** The wire
 * sends them under one shape, and {@see VersionInUse} is why this reading
 * cannot hand the first one to {@see TakingAnUpdate::agreed()} — `N2-R20`'s
 * refusal, made structural rather than left to the screen that reads this.
 */
final readonly class Upkeep
{
    private function __construct(
        private HowCurrent $how,
        private Releases $releases,
        private Services $changing,
        private HowServicesTookIt $went,
        private ?VersionInUse $inUse = null,
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
    public static function reported(
        HowCurrent $how,
        Releases $releases,
        Services $changing,
        HowServicesTookIt $went,
    ): self {
        return new self($how, $releases, $changing, $went);
    }

    /** The same reading, where the stack named the release in use. */
    public static function runningOn(
        HowCurrent $how,
        VersionInUse $inUse,
        Releases $releases,
        Services $changing,
        HowServicesTookIt $went,
    ): self {
        return new self($how, $releases, $changing, $went, $inUse);
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
     * **Named `inUse` rather than `running`**, which is what
     * {@see Daemons::running()} and {@see Supervising::running()} already mean
     * one file over: there they are about services being up, and here it was
     * about a version. One word for two ideas in neighbouring types is what
     * kept the type they shared out of sight. The word an operator reads is
     * still *Running*, in the catalogue, for the reason
     * {@see WhatToDoWithIt::asked()} gives about `up` and *start*.
     *
     * @template TNamed of object
     * @template TUnstated of object
     *
     * @param  Closure(VersionInUse): TNamed  $named
     * @param  Closure(): TUnstated  $unstated
     * @return TNamed|TUnstated
     */
    public function inUse(Closure $named, Closure $unstated): object
    {
        return $this->inUse instanceof VersionInUse ? $named($this->inUse) : $unstated();
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
     * The services taking an update would change.
     *
     * What `N2-R17`'s confirmation names. Carried on the reading rather than
     * asked for when the operator taps, because a list fetched after the yes is
     * a list of whatever the stack had by then — and the confirmation is only
     * worth anything if what was named is what gets done.
     */
    public function changing(): Services
    {
        return $this->changing;
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
        return $this->inUse instanceof VersionInUse && $this->inUse->wasWithdrawn();
    }

    /**
     * Whether there is an update to offer at all.
     *
     * Both halves, because either alone would be wrong: a stack that says
     * pending with every release withdrawn has nothing to offer, and a stack
     * that says current is not asked further.
     */
    public function hasSomethingToOffer(): bool
    {
        return $this->how->hasSomethingWaiting() && ! $this->waiting()->isEmpty();
    }

    /**
     * What became of each service the last applied update touched.
     *
     * Part of this reading rather than a second errand because it arrives in
     * the same payload and answers the other half of the same question. An
     * operator opening this screen is asking *where am I* — and where they are
     * includes an update that went half way last night, which a screen showing
     * only what is waiting would leave them to discover from the services.
     */
    public function howItWent(): HowServicesTookIt
    {
        return $this->went;
    }
}
