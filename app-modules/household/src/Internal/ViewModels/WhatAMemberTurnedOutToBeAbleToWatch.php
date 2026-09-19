<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * What came back when a member's shelf was asked for.
 *
 * The same four answers every member-facing reading has — signed out, stopped,
 * told, and the one this screen adds: told, and the library was not the core's
 * to hand over.
 *
 * **Out of reach is not an empty shelf and the template cannot confuse them.**
 * Both would be drawn by the same `@forelse` and they say opposite things to
 * whoever is reading: one is *you have nothing here* and the other is *your
 * collection could not be reached*. Telling somebody the first when the second
 * is true is the failure this screen exists around.
 *
 * @param list<WhatOneHoldingSays> $holdings
 * @param list<string>             $reasons
 */
final readonly class WhatAMemberTurnedOutToBeAbleToWatch
{
    /**
     * @param list<WhatOneHoldingSays> $holdings
     * @param list<string>             $reasons
     */
    private function __construct(
        public bool $isSignedIn,
        public string $met,
        public string $remedy,
        public bool $isOutOfReach,
        public array $holdings,
        public array $reasons,
    ) {}

    /**
     * The core answered, and this is the shelf.
     *
     * An empty list is an ordinary answer: a member with nothing on their
     * shelf is told so in as many words.
     *
     * @param list<WhatOneHoldingSays> $holdings
     */
    public static function these(array $holdings): self
    {
        return new self(
            isSignedIn: true,
            met: '',
            remedy: '',
            isOutOfReach: false,
            holdings: $holdings,
            reasons: [],
        );
    }

    /**
     * The core answered and the library was not its to give.
     *
     * The sentences are the core's own, carried rather than rewritten — which
     * is what keeps this screen from inventing a reason for a route it has no
     * view of.
     *
     * @param list<string> $reasons
     */
    public static function outOfReach(array $reasons): self
    {
        return new self(
            isSignedIn: true,
            met: '',
            remedy: '',
            isOutOfReach: true,
            holdings: [],
            reasons: $reasons,
        );
    }

    /** This device no longer holds a session for that stack. */
    public static function theSessionEnded(): self
    {
        return new self(
            isSignedIn: false,
            met: '',
            remedy: '',
            isOutOfReach: false,
            holdings: [],
            reasons: [],
        );
    }

    /** Something stood in the way, and this is what the member met. */
    public static function somethingStopped(string $met, string $remedy, bool $signedOut): self
    {
        return $signedOut
            ? self::theSessionEnded()
            : new self(
                isSignedIn: true,
                met: $met,
                remedy: $remedy,
                isOutOfReach: false,
                holdings: [],
                reasons: [],
            );
    }

    /**
     * Whether the screen has a shelf to draw.
     *
     * The one expression of the rule, so the template asks rather than
     * restates it — and it is what keeps the empty arm of the list honest: a
     * `@forelse` reaches its `@empty` only inside this branch, so *there is
     * nothing on your shelf* is drawn where the library answered and never
     * where it could not be reached.
     */
    public function cameBack(): bool
    {
        return $this->isSignedIn && $this->met === '' && ! $this->isOutOfReach;
    }
}
