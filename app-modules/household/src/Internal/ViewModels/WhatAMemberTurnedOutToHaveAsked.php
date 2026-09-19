<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * What asking a stack what a member asked for produced, flattened for a template.
 *
 * The sibling of {@see WhatAMemberTurnedOutToBeOwed} and shaped the same way, for
 * its reason: Blade has no `either()` and cannot be given one, so the fold happens
 * once in a presenter and the template reads fields.
 *
 * **Four states, and two of them look identical if they are folded.** A stack that
 * answered with requests; a stack that answered with none; a stack that refused;
 * and a device holding no session at all. The middle two are the pair worth
 * keeping apart — both arrive as no rows, and a template drawing a list would show
 * an empty one for each. One says you have asked for nothing, and the other says
 * nobody could find out what you asked for.
 *
 * **A state and never a stage.** Each row carries what the thing is called, where
 * it stands in the household's words, and the reason it was refused where it was.
 * What is deliberately not here is the machinery: no queue position, no
 * percentage, no service name, no identifier for the thing on the library's own
 * shelves. A member waiting for a film is owed an answer about the film.
 *
 * **Nothing here is another member's.** The rows are the signed-in member's own,
 * because the core narrowed the reading to them — so this type has no member on
 * it, and there is no field a screen could show the wrong person's name in.
 */
final readonly class WhatAMemberTurnedOutToHaveAsked
{
    /**
     * Private, so the named constructors are the only way in.
     *
     * The combination that makes no sense — not signed in *and* something met —
     * has no spelling, for {@see WhatAMemberTurnedOutToBeOwed}'s reason: the app
     * did not get as far as asking, so it met nothing.
     *
     * @param list<WhatOneOfTheirRequestsSays> $rows what was asked for, in the order
     *        the stack listed it
     */
    private function __construct(
        public bool $isSignedIn,
        public string $met,
        public string $remedy,
        public array $rows,
    ) {}

    /**
     * The stack answered, and this is what they have asked for.
     *
     * An empty list is an ordinary answer: somebody who has asked for nothing is
     * told so in as many words, rather than shown a screen that simply has
     * nothing on it.
     *
     * @param list<WhatOneOfTheirRequestsSays> $rows
     */
    public static function these(array $rows): self
    {
        return new self(isSignedIn: true, met: '', remedy: '', rows: $rows);
    }

    /**
     * This device no longer holds a session for that stack.
     *
     * Nothing was met, because nothing was asked. The remedy is a screen rather
     * than a sentence, which is why there is no key here to carry one.
     */
    public static function theSessionEnded(): self
    {
        return new self(isSignedIn: false, met: '', remedy: '', rows: []);
    }

    /**
     * Something stood in the way, and this is what the member met.
     *
     * Both keys come off the obstacle, which owns them, and whether a refusal
     * means signed out is the obstacle's answer rather than this module's — so
     * this screen cannot come to disagree with the ones beside it about whether
     * somebody is signed in.
     */
    public static function somethingStopped(string $met, string $remedy, bool $signedOut): self
    {
        return $signedOut
            ? self::theSessionEnded()
            : new self(isSignedIn: true, met: $met, remedy: $remedy, rows: []);
    }

    /**
     * Whether the screen has its own content to draw.
     *
     * The one expression of the rule, so the template asks rather than restates
     * it, and what keeps the empty arm honest: a `@forelse` reaches its `@empty`
     * only inside this branch, so *you have asked for nothing* is drawn where the
     * stack answered and never where it would not say.
     */
    public function cameBack(): bool
    {
        return $this->isSignedIn && $this->met === '';
    }
}
