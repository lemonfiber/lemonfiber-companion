<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Remedies;

/**
 * One row of a report, flattened for a template to read.
 *
 * {@see \Modules\Kernel\Api\WhatTheCheckSaid::either()} answers with an object,
 * so that a caller cannot take a meaning out without saying what happens when a
 * check had nothing to say. That is the right shape for a value and the wrong
 * one for a Blade file, which has no `either()` and cannot be given one — so
 * the screen folds each finding into this once and the template reads fields.
 *
 * **The meaning and the remedies were on the wire and going nowhere.**
 * `Finding::said()` has carried them since the translation was written, and
 * until this existed no screen asked: an operator saw *"The disk is nearly
 * full — Needs attention"* and not what that meant for them or what to do.
 * `N2-R3` is the requirement, and the words are the core's own rather than this
 * app's, which is why they are rendered rather than translated.
 *
 * **The category is carried on every row**, wrong or not. A report listing ten
 * checks in the engine's own order is a list an operator scans for the part of
 * the machine they are worried about, and the order is deliberately not changed
 * to group them — reordering would be this app second-guessing the engine about
 * which finding matters most. Saying which part each is about does the same
 * work without taking that decision.
 *
 * **A row with nothing wrong carries neither**, and the empty strings are what
 * the template branches on. A passing check has no meaning to explain and no
 * remedy to offer, and inventing a sentence for one would be this app writing
 * words the machine did not say.
 *
 * `Internal` because it is a detail of how this surface reads one value, and
 * `E2`'s promise is that anything here can be renamed without reading another
 * module.
 */
final readonly class WhatOneFindingSays
{
    /**
     * @param string   $title    what the check is called, in the core's words
     * @param string   $about    the key for which part of the machine it is about
     * @param string   $verdict  the key for what the verdict is called
     * @param string   $code     the identifier an operator quotes, or empty
     * @param string   $meaning  what it means for them, or empty
     * @param Remedies $remedies what to try, likeliest first, empty where none
     */
    private function __construct(
        public string $title,
        public string $about,
        public string $verdict,
        public string $code,
        public string $meaning,
        public Remedies $remedies,
    ) {}

    /**
     * The one place a finding becomes a row.
     *
     * The `either()` is answered here rather than in the screen, so a screen
     * showing findings is a loop over this and not a fold per row.
     */
    public static function in(Finding $finding): self
    {
        $said = $finding->said();

        return $said->either(
            nothingWrong: static fn(): self => new self(
                title: $finding->title(),
                about: $finding->category()->saidOnTheScreen(),
                verdict: $finding->conclusion()->saidOnTheScreen(),
                code: '',
                meaning: '',
                remedies: Remedies::none(),
            ),
            wentWrong: static fn(Code $code, string $meaning, Remedies $remedies): self => new self(
                title: $finding->title(),
                about: $finding->category()->saidOnTheScreen(),
                verdict: $finding->conclusion()->saidOnTheScreen(),
                code: $code->shown(),
                meaning: $meaning,
                // Every one of them, in the order the engine gave. `likeliest()`
                // exists for a screen with room for one line, and this screen
                // has room for the list — an operator whose first remedy did
                // not work would otherwise have nowhere to find the second.
                remedies: $remedies,
            ),
        );
    }

    /** Whether there is anything to explain, which is what the template asks. */
    public function explainsItself(): bool
    {
        return $this->meaning !== '';
    }
}
