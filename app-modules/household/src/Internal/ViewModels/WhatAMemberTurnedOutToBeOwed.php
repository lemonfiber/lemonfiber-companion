<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * What asking a stack what a member is owed produced, flattened for a template.
 *
 * Blade has no `either()` and cannot be given one, so the fold happens once in
 * {@see \Modules\Household\Internal\Presenters\HowWhatAMemberIsOwedReads} and
 * the template reads fields.
 *
 * **Four states, and two of them look identical if they are folded.** A stack
 * that answered with sentences; a stack that answered with none; a stack that
 * refused; and a device holding no session at all. The middle two are the pair
 * this whole screen exists to keep apart — both arrive as no sentences, and a
 * template drawing a list would show an empty one for each. One of them says
 * there is nothing to tell you and the other says this was not yours to ask.
 *
 * **Nothing here is the operator's.** The fields are sentences the core wrote
 * to the member and two catalogue keys off an obstacle, so a member surface can
 * hold this without holding a fault, a code, a remedy or another member.
 */
final readonly class WhatAMemberTurnedOutToBeOwed
{
    /**
     * Private, so the named constructors are the only way in.
     *
     * The combination that makes no sense — not signed in *and* something met —
     * has no spelling rather than being a thing to remember not to write: the
     * app did not get as far as asking, so it met nothing.
     *
     * @param list<string> $sentences what the core wrote, in the order it wrote it
     */
    private function __construct(
        public bool $isSignedIn,
        public string $met,
        public string $remedy,
        public array $sentences,
    ) {}

    /**
     * The stack answered, and this is what it says the member is owed.
     *
     * @param list<string> $sentences
     */
    public static function these(array $sentences): self
    {
        return new self(isSignedIn: true, met: '', remedy: '', sentences: $sentences);
    }

    /**
     * This device no longer holds a session for that stack.
     *
     * Nothing was met, because nothing was asked. The remedy is a screen rather
     * than a sentence, which is why there is no key here to carry one.
     */
    public static function theSessionEnded(): self
    {
        return new self(isSignedIn: false, met: '', remedy: '', sentences: []);
    }

    /**
     * Something stood in the way, and this is what the member met.
     *
     * Both keys come off the obstacle, which owns them — what happened and what
     * to do about it are not the same sentence, and neither is one this module
     * wrote. A refused credential is being signed out rather than something to
     * report, and that line is drawn by the obstacle rather than here, so this
     * screen cannot come to disagree with the ones beside it about whether
     * somebody is signed in.
     */
    public static function somethingStopped(string $met, string $remedy, bool $signedOut): self
    {
        return $signedOut
            ? self::theSessionEnded()
            : new self(isSignedIn: true, met: $met, remedy: $remedy, sentences: []);
    }

    /**
     * Whether the screen has its own content to draw.
     *
     * The one expression of the rule, so the template asks rather than
     * restates it. It is also what keeps the empty arm of the list honest: a
     * `@forelse` reaches its `@empty` only inside this branch, so *there is
     * nothing to tell you* is drawn where the stack answered and never where
     * it would not say.
     */
    public function cameBack(): bool
    {
        return $this->isSignedIn && $this->met === '';
    }
}
