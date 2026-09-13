<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Severity;

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
 * **The cause is resolved to the other row's title, not shown as its id.** The
 * engine attributes one finding to another by check identifier — `vpn.up` —
 * which is the right thing on a wire and jargon on a phone. So the fold is
 * given the whole run and looks the identifier up: an operator reads *because
 * The tunnel*, not *because vpn.up*. Where the report names a check it does not
 * contain, the identifier is shown as it arrived rather than hidden; that is
 * the engine having a fault, and an identifier is something a person can quote
 * to somebody who can fix it.
 *
 * **The category is carried on every row**, wrong or not. A report listing ten
 * checks in the engine's own order is a list an operator scans for the part of
 * the machine they are worried about, and the order is deliberately not changed
 * to group them — reordering would be this app second-guessing the engine about
 * which finding matters most. Saying which part each is about does the same
 * work without taking that decision.
 *
 * **How much it costs is shown beside the verdict, not instead of it.** They
 * answer different questions — the verdict is whether the check passed and the
 * severity is what the answer costs — and two failed checks where one puts data
 * at risk must not read the same. It is also the key
 * {@see \Modules\Health\Api\Queries\WorstFirst} orders by first, so a row
 * that is higher up for a reason says what that reason was.
 *
 * **A row with nothing wrong carries neither**, and the empty strings are what
 * the template branches on. A passing check has no meaning to explain and no
 * remedy to offer, and inventing a sentence for one would be this app writing
 * words the machine did not say.
 *
 * **A row with no answer carries a sentence and no code.** `unverified` and
 * `skipped` are checks that produced no verdict, so there is nothing to quote
 * and nothing to grade — but there is a reason, and for an unverified check
 * something to do about it. Those go in the same two fields a failure uses,
 * because an operator reading a row wants to know what it means and what to do
 * about it whichever outcome produced it. The empty code is the difference, and
 * it is a true one: there is no identifier to search for.
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
     * @param string   $cost     the key for how much it matters, or empty
     * @param string   $service  which service this is about, or empty
     * @param string   $because  the title of what explains this, or empty
     * @param Remedies $remedies what to try, likeliest first, empty where none
     */
    private function __construct(
        public string $title,
        public string $about,
        public string $verdict,
        public string $code,
        public string $meaning,
        public string $cost,
        public string $service,
        public string $because,
        public Remedies $remedies,
    ) {}

    /**
     * The one place a finding becomes a row.
     *
     * The `either()` is answered here rather than in the screen, so a screen
     * showing findings is a loop over this and not a fold per row.
     */
    public static function in(Finding $finding, Findings $run): self
    {
        $said = $finding->said();
        $service = self::serviceOf($finding);
        $because = self::becauseOf($finding, $run);

        return $said->either(
            nothingWrong: static fn(): self => new self(
                title: $finding->title(),
                about: $finding->category()->saidOnTheScreen(),
                verdict: $finding->conclusion()->saidOnTheScreen(),
                code: '',
                meaning: '',
                cost: '',
                service: $service,
                because: $because,
                remedies: Remedies::none(),
            ),
            wentWrong: static fn(
                Code $code,
                string $meaning,
                Remedies $remedies,
                Severity $severity,
            ): self => new self(
                title: $finding->title(),
                about: $finding->category()->saidOnTheScreen(),
                verdict: $finding->conclusion()->saidOnTheScreen(),
                code: $code->shown(),
                meaning: $meaning,
                cost: $severity->saidOnTheScreen(),
                service: $service,
                because: $because,
                // Every one of them, in the order the engine gave. `likeliest()`
                // exists for a screen with room for one line, and this screen
                // has room for the list — an operator whose first remedy did
                // not work would otherwise have nowhere to find the second.
                remedies: $remedies,
            ),
            couldNotSay: static fn(string $reason, Remedies $remedies): self => new self(
                title: $finding->title(),
                about: $finding->category()->saidOnTheScreen(),
                verdict: $finding->conclusion()->saidOnTheScreen(),
                // No code and no cost, because these outcomes carry neither.
                // Nothing was graded — a check that could not run produced no
                // judgement, and a word here would be this app inventing one.
                // The template branches on the meaning, so a row with a reason
                // and neither of the others still explains itself.
                code: '',
                meaning: $reason,
                cost: '',
                service: $service,
                because: $because,
                remedies: $remedies,
            ),
        );
    }

    /** Whether there is anything to explain, which is what the template asks. */
    public function explainsItself(): bool
    {
        return $this->meaning !== '';
    }

    /** Which service this row is about, or empty where it is about the machine. */
    private static function serviceOf(Finding $finding): string
    {
        return $finding->whatItIsAbout()->either(
            theMachine: static fn(): AsText => AsText::nothing(),
            theService: static fn(string $service): AsText => AsText::of($service),
        )->said;
    }

    /**
     * What explains this row, named the way the operator will recognise it.
     *
     * The run is searched for the check the engine pointed at, and its title is
     * what a person reads. A check the report does not hold falls back to the
     * identifier, which is honest rather than tidy: something is wrong with the
     * report and the operator has a string they can quote.
     */
    private static function becauseOf(Finding $finding, Findings $run): string
    {
        return $finding->whatExplainsIt()->either(
            alone: static fn(): AsText => AsText::nothing(),
            explained: static fn(Check $cause): AsText => AsText::of(self::titleOf($cause, $run)),
        )->said;
    }

    /** @return string the cause's title, or its identifier where the run has no such row */
    private static function titleOf(Check $cause, Findings $run): string
    {
        foreach ($run as $finding) {
            if ($finding->check()->shown() === $cause->shown()) {
                return $finding->title();
            }
        }

        return $cause->shown();
    }
}
