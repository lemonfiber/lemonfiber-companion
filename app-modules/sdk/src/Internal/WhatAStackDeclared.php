<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\Capabilities;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\WhetherItIsOffered;

/**
 * What one stack said it can do, when, and to which session.
 *
 * Three ways asking can end, and each answers a path differently: the stack
 * declared what it serves; it is too old to have a declaration at all; or it
 * could not be asked. Held with the moment it was asked and the session it was
 * asked with, because what an account may ask for is part of the answer, and
 * an answer is an opinion once it is old.
 */
final readonly class WhatAStackDeclared
{
    private function __construct(
        private ?Capabilities $declared,
        private bool $tooOldToSay,
        private Session $askedWith,
        private Instant $at,
    ) {}

    /** The stack said what it serves. */
    public static function declared(Capabilities $declared, Session $askedWith, Instant $at): self
    {
        return new self($declared, tooOldToSay: false, askedWith: $askedWith, at: $at);
    }

    /** The stack has no way to say what it serves, so it is older than the declaration. */
    public static function tooOldToSay(Session $askedWith, Instant $at): self
    {
        return new self(null, tooOldToSay: true, askedWith: $askedWith, at: $at);
    }

    /** The stack could not be asked, or what it said could not be read. */
    public static function unasked(Session $askedWith, Instant $at): self
    {
        return new self(null, tooOldToSay: false, askedWith: $askedWith, at: $at);
    }

    /**
     * What a request at this path is, on this stack.
     *
     * A stack too old to say offers nothing but the way out, which
     * {@see WhatAnOldStackIsStillAsked} names with why. One that could not be
     * asked offers everything, and asking reports why it could not.
     */
    public function at(Ability $path): WhetherItIsOffered
    {
        if ($this->declared instanceof Capabilities) {
            return $this->declared->whetherItOffers($path);
        }

        return $this->tooOldToSay && ! WhatAnOldStackIsStillAsked::at($path)
            ? WhetherItIsOffered::NeedsANewerLemonfiber
            : WhetherItIsOffered::NotKnown;
    }

    /** Whether this is what the stack says to this session: another session is another account, and its answer is its own. */
    public function isFor(Session $session): bool
    {
        return $this->askedWith->is($session);
    }

    /**
     * Whether a screen opening now asks the stack again.
     *
     * An answer is held as long as a screen showing what changes slowly would
     * hold one, so a screen opened after a break asks the stack again. A stack
     * that could not be asked is asked again as soon as a broken stream would
     * be opened again, because it is a machine that just failed to answer.
     */
    public function isOlderThanABreakAt(Instant $now): bool
    {
        $lasts = $this->declared instanceof Capabilities || $this->tooOldToSay
            ? HowOftenAScreenLooks::WhileOpen
            : HowOftenAScreenLooks::AfterABreak;

        return $now->epochSeconds() - $this->at->epochSeconds() >= $lasts->seconds();
    }
}
