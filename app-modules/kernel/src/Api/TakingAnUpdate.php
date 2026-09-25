<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * An update the stack offered, against what it will change.
 *
 * {@see Confirmed}'s argument applied to an update, and the same argument
 * {@see AgreedTo} makes for a verb: {@see KeepingCurrent::take()} takes one of
 * these, and the only way to make one is from a reading that offered an update.
 *
 * **An update is the stack's, not a release's.** The stack moves each service
 * onto the version its own build pins, so what is agreed to is the set of
 * services that would move. No release is named here, because none is chosen:
 * the release history explains where the pins came from and is not a menu.
 *
 * **The services are carried rather than looked up later.** The
 * confirmation names what it would change, which is only worth anything if
 * what was named is what gets done.
 */
final readonly class TakingAnUpdate
{
    private function __construct(
        private Services $changing,
        private Services $cannotBePutBack,
    ) {}

    /**
     * The update a reading offered.
     *
     * Refuses a reading with nothing to offer by throwing {@see NothingToTake},
     * so an update the stack did not report as available cannot be agreed to
     * by any caller.
     */
    public static function offeredBy(Upkeep $upkeep): self
    {
        if (! $upkeep->hasSomethingToOffer()) {
            throw NothingToTake::from($upkeep->againstThePins());
        }

        return new self($upkeep->changing(), $upkeep->cannotBePutBack());
    }

    /**
     * The name lemonfiber's surface asks for this by.
     *
     * Here rather than on an enum of its own, because there is exactly one
     * thing to do about an update and a single-case enum would be a shape
     * pretending at a choice that does not exist. What matters is that the name
     * is spelled once, in the kernel, and never at a call site — the app
     * refuses an app that can name any action a stack offers, `setup` among
     * them, and the way that happens is a literal in an adapter.
     *
     * {@see WhatToDoWithIt::asked()} is the same method one verb over, and for
     * the same reason: the operator's word and the wire's are allowed to
     * differ, so neither can be read off the other.
     */
    public function asked(): string
    {
        return 'update';
    }

    /**
     * The services this was agreed about.
     *
     * Handed out as the collection so the one caller that has to put names on
     * a wire iterates for them — an adapter reaching into a value object for
     * its insides is the thing typed collections exist to stop.
     */
    public function changing(): Services
    {
        return $this->changing;
    }

    /**
     * The services it would change in a way nothing puts back.
     *
     * Carried for the reason {@see changing()} is, and it is the stronger case
     * of the two: the rest of an evening can be undone afterwards and these
     * cannot, so a list re-read after the yes would be a warning about
     * whatever the stack had by then rather than about what was agreed to.
     */
    public function cannotBePutBack(): Services
    {
        return $this->cannotBePutBack;
    }

    /**
     * Whether anything it would change cannot be put back.
     *
     * Asked rather than left to a screen counting the collection, so *is there
     * a warning to draw* has one answer and a template cannot arrive at a
     * different one by testing emptiness the other way round.
     */
    public function cannotBeWhollyUndone(): bool
    {
        return ! $this->cannotBePutBack->isEmpty();
    }
}
