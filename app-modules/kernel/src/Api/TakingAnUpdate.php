<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * An update the operator said yes to, against what it will change.
 *
 * {@see Confirmed}'s argument applied to an update, and the same argument
 * {@see AgreedTo} makes for a verb: the way `N2-R17` gets broken is never
 * deliberate — a screen draws the pending release, the button is right there,
 * and a tap handler calls the thing that applies it.
 *
 * So {@see KeepingCurrent::take()} takes one of these, and the only way to make
 * one names the release and the services together. Rendering an upkeep reading
 * produces no `TakingAnUpdate` and cannot be made to.
 *
 * **The services are carried rather than looked up later.** `N2-R17` wants the
 * confirmation to name what it would change, which is only worth anything if
 * what was named is what gets done. An update applied against a list re-read
 * after the yes would be an update to whatever the stack had by then, confirmed
 * against a screen that is no longer true.
 */
final readonly class TakingAnUpdate
{
    private function __construct(
        private Release $release,
        private Services $changing,
    ) {}

    /**
     * What the operator was shown and agreed to.
     *
     * The services travel as a {@see Services} rather than an array, which is
     * `D1`: what is in a list of names has to live somewhere other than in
     * whoever last wrote a `foreach`.
     */
    public static function agreed(Release $release, Services $changing): self
    {
        return new self($release, $changing);
    }

    /**
     * The name lemonfiber's surface asks for this by.
     *
     * Here rather than on an enum of its own, because there is exactly one
     * thing to do about an update and a single-case enum would be a shape
     * pretending at a choice that does not exist. What matters is that the name
     * is spelled once, in the kernel, and never at a call site — `N1-R4`
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

    public function release(): Release
    {
        return $this->release;
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
     * Whether this update leaves the stack alone.
     *
     * A release that changes no service is a changelog entry rather than an
     * evening, and a screen can say so instead of asking somebody to confirm
     * nothing.
     */
    public function changesNothing(): bool
    {
        return $this->changing->isEmpty();
    }
}
