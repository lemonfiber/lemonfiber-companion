<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\Obstacle;

/**
 * Whether a screen has a reading to draw, and what stopped it where it has not.
 *
 * Three facts that always travel together — the session, what was met, what to
 * do about it — written out separately on eight view models and rebuilt from
 * the same three arguments in twenty-two places. That is twenty-two chances for
 * one of them to drift, and `N1-R1` asks for parity across surfaces rather than
 * parity by everybody remembering.
 *
 * {@see \Modules\Operator\View\Components\WhatStoppedTheReading} named this as
 * the missing piece in its own docblock: it owns what is *drawn* when a reading
 * did not come back, and each screen owned whether to draw its own content
 * beside it. Two expressions of one rule, and the second was the inverse of the
 * first — `@if ($this->answer()->isSignedIn && $this->answer()->met === '')`,
 * repeated on seven templates. An inverse written out seven times is a rule
 * that can disagree with itself, and nothing would have said so: both halves
 * drawing nothing is a blank screen, and both halves drawing is a screen with
 * the obstacle and the content at once.
 *
 * **The named constructors are the vocabulary, and there are only three
 * situations.** A reading that came back, a session that has ended, and
 * something met on the way. Anything else is one of those three spelled
 * differently.
 *
 * **Which situation a refused credential is, is decided once here.**
 * `Obstacle::meansWeAreSignedOut()` owns that, and every presenter used to ask
 * it separately — the comment warning that a presenter re-deciding it is how
 * two screens come to disagree was on one of eight, which is exactly the shape
 * of a rule kept by remembering.
 */
final readonly class HowTheReadingWent
{
    /**
     * Private, so the three named constructors are the only way in.
     *
     * The combination that makes no sense — not signed in *and* something met —
     * has no spelling, rather than being a thing to remember not to write. The
     * app did not get as far as asking, so it met nothing.
     */
    private function __construct(
        public bool $isSignedIn,
        public string $met,
        public string $remedy,
    ) {}

    /** The stack answered and there is something to draw. */
    public static function itCameBack(): self
    {
        return new self(isSignedIn: true, met: '', remedy: '');
    }

    /**
     * This device no longer holds a session for that stack.
     *
     * Nothing was met, because nothing was asked. `N1-R44` puts the remedy on a
     * screen rather than in a sentence, which is why there is no key here to
     * carry one.
     */
    public static function theSessionEnded(): self
    {
        return new self(isSignedIn: false, met: '', remedy: '');
    }

    /**
     * Something stood in the way, and this is what the operator met.
     *
     * A refused credential is being signed out rather than an obstacle to
     * report, and that reading happens here rather than at each caller.
     * `N1-R10` wants the remedy beside what happened — one is a fact about the
     * world and the other is advice — so both come off the obstacle and neither
     * is a sentence a presenter wrote.
     */
    public static function somethingStopped(Obstacle $why): self
    {
        return $why->meansWeAreSignedOut()
            ? self::theSessionEnded()
            : new self(isSignedIn: true, met: $why->said(), remedy: $why->remedy());
    }

    /**
     * Whether the screen has its own content to draw.
     *
     * The one expression of the rule, where there were seven inverses of it.
     * A template asks this; it does not restate it.
     */
    public function cameBack(): bool
    {
        return $this->isSignedIn && $this->met === '';
    }
}
