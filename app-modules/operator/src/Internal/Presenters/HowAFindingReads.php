<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Severity;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\WhatOneFindingSays;

/**
 * One finding, as the row a report draws.
 *
 * **The run is handed in, not gone and got.** `F2`: a presenter takes the
 * answer and never the means of getting it, and the run is the answer — a
 * {@see Findings} is a typed collection, which is a value a test can state.
 * The alternative is the shape this rule exists to refuse: a presenter holding
 * the port that asks the stack, so that reading one row opens a connection and
 * the thing a test measures becomes the fake rather than the decision.
 *
 * Held for the whole listing rather than passed per row, because every row is
 * resolved against the same run and a fold that took it as an argument could be
 * handed a different one each time.
 *
 * **The meaning and the remedies are on the wire and would otherwise go
 * nowhere.** `Finding::said()` has carried them since the translation was
 * written; without this an operator sees *"The disk is nearly full — Needs
 * attention"* and not what that means for them or what to do. `N2-R3` is the
 * requirement, and the words are the core's own rather than this app's, which
 * is why they are rendered rather than translated.
 *
 * **The cause is resolved to the other row's title, not shown as its id.** The
 * engine attributes one finding to another by check identifier — `vpn.up` —
 * which is the right thing on a wire and jargon on a phone. So the identifier
 * is looked up in the run: an operator reads *because The tunnel*, not *because
 * vpn.up*. Where the report names a check it does not contain, the identifier
 * is shown as it arrived rather than hidden; that is the engine having a fault,
 * and an identifier is something a person can quote to somebody who can fix it.
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
 * {@see \Modules\Health\Api\Queries\WorstFirst} orders by first, so a row that
 * is higher up for a reason says what that reason was.
 */
final readonly class HowAFindingReads
{
    public function __construct(private Findings $run) {}

    /**
     * The one place a finding becomes a row.
     *
     * The `either()` is answered here rather than in the screen, so a screen
     * showing findings is a loop over this and not a fold per row.
     */
    public function of(Finding $finding): WhatOneFindingSays
    {
        $said = $finding->said();
        $service = $this->serviceOf($finding);
        $because = $this->becauseOf($finding);

        return $said->either(
            nothingWrong: static fn(): WhatOneFindingSays => new WhatOneFindingSays(
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
            ): WhatOneFindingSays => new WhatOneFindingSays(
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
            couldNotSay: static fn(string $reason, Remedies $remedies): WhatOneFindingSays => new WhatOneFindingSays(
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

    /** Which service this row is about, or empty where it is about the machine. */
    private function serviceOf(Finding $finding): string
    {
        return $finding->whatItIsAbout()->either(
            theMachine: static fn(): AsText => AsText::nothing(),
            theService: static fn(ServiceId $service): AsText => AsText::of($service->named()),
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
    private function becauseOf(Finding $finding): string
    {
        return $finding->whatExplainsIt()->either(
            alone: static fn(): AsText => AsText::nothing(),
            explained: fn(Check $cause): AsText => AsText::of($this->titleOf($cause)),
        )->said;
    }

    /** @return string the cause's title, or its identifier where the run has no such row */
    private function titleOf(Check $cause): string
    {
        foreach ($this->run as $finding) {
            if ($finding->check()->shown() === $cause->shown()) {
                return $finding->title();
            }
        }

        return $cause->shown();
    }
}
