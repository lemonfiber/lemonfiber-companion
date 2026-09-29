<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AChangeAndWhy;
use Modules\Kernel\Api\AChangePutBack;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Kernel\Api\ARunToPutBack;
use Modules\Kernel\Api\ChangesAndWhy;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhenItWasMade;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Operator\Internal\ViewModels\AChangeAndWhyAsShown;
use Modules\Operator\Internal\ViewModels\AChangeGoneBackAsShown;
use Modules\Operator\Internal\ViewModels\ARefusalAsShown;
use Modules\Operator\Internal\ViewModels\HowPuttingARunBackWent;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatPuttingARunBackWouldShow;

/**
 * Putting a run back, as the template draws it: the record's rows first, and
 * what became of the yes after.
 *
 * Two families of method for two values, so what the record says would go
 * and what the stack says went never share a model. `F2` — data in, view
 * model out, with the moment ages are measured against handed in.
 */
final readonly class HowARunBackReads
{
    /** The record's rows for the run, which are what is agreed to. */
    public function shown(ARunToPutBack $run, Instant $now): WhatPuttingARunBackWouldShow
    {
        $rows = [];

        foreach ($run as $change) {
            $rows[] = new HowARecordedChangeReads()->in($change);
        }

        $when = $run->when(
            made: static fn(WhenItWasMade $made): AgoAsShown => $made->either(
                at: static fn(Instant $at): AgoAsShown => AgoAsShown::from(HowLongAgo::since($at, $now), $at, $now),
                unreadable: AgoAsShown::undated(...),
            ),
            nowhere: AgoAsShown::live(...),
        );

        return new WhatPuttingARunBackWouldShow(
            went: HowTheReadingWent::itCameBack(),
            namesARun: true,
            isOnTheRecord: $rows !== [],
            goesBack: $run->goesBack(),
            whenSaid: $when->said,
            whenCount: $when->count,
            alongside: $run->alongside(),
            changes: $rows,
        );
    }

    /** The record could not be read, and this is what stood in the way. */
    public function notShown(Obstacle $why): WhatPuttingARunBackWouldShow
    {
        return $this->nothingShown(HowTheReadingWent::somethingStopped($why));
    }

    /** This device no longer holds a session for the stack, so nothing was asked. */
    public function notAsked(): WhatPuttingARunBackWouldShow
    {
        return $this->nothingShown(HowTheReadingWent::theSessionEnded());
    }

    /** The screen was opened naming no run, so there is nothing to ask about. */
    public function namesNoRun(): WhatPuttingARunBackWouldShow
    {
        return $this->nothingShown(HowTheReadingWent::itCameBack(), namesARun: false);
    }

    /** The stack is still putting it back. */
    public function running(): HowPuttingARunBackWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), isWorking: true);
    }

    /** The stack has no outcome for it any more, which is not the same as it failing. */
    public function ended(): HowPuttingARunBackWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), hasEnded: true);
    }

    /** Putting it back, or asking after it, met this instead. */
    public function met(Obstacle $why): HowPuttingARunBackWent
    {
        return $this->following(HowTheReadingWent::somethingStopped($why));
    }

    /** The stack answered and would not put the run back, and this is what it said and named. */
    public function refused(ARefusalInItsWords $why): HowPuttingARunBackWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), refused: new HowARefusalReads()->inItsWords($why));
    }

    /** This device no longer holds a session for the stack it was put back on. */
    public function signedOut(): HowPuttingARunBackWent
    {
        return $this->following(HowTheReadingWent::theSessionEnded());
    }

    /** The stack's report of what putting it back came to. */
    public function done(ARunPutBack $report): HowPuttingARunBackWent
    {
        $reversed = [];

        foreach ($report->reversed() as $change) {
            $reversed[] = $this->goneBack($change);
        }

        $rehearsed = $report->rehearsed() === WhetherItWasRehearsed::Rehearsed;
        $headline = match (true) {
            $rehearsed && $report->leftNothing() => 'stacks.run_back.would.all',
            $rehearsed => 'stacks.run_back.would.not_all',
            $report->leftNothing() => 'stacks.run_back.did.all',
            default => 'stacks.run_back.did.not_all',
        };

        return new HowPuttingARunBackWent(
            went: HowTheReadingWent::itCameBack(),
            isWorking: false,
            hasEnded: false,
            hasReport: true,
            rehearsed: $rehearsed,
            leftNothing: $report->leftNothing(),
            headline: $headline,
            reversedSaid: $rehearsed ? 'stacks.run_back.would.reversed' : 'stacks.run_back.did.reversed',
            noneReversed: $rehearsed ? 'stacks.run_back.would.none_reversed' : 'stacks.run_back.did.none_reversed',
            reversed: $reversed,
            left: $this->saidOf($report->left()),
            noted: $this->saidOf($report->noted()),
            refused: null,
        );
    }

    /** One change that went back, with the key for what going back did. */
    private function goneBack(AChangePutBack $change): AChangeGoneBackAsShown
    {
        return new AChangeGoneBackAsShown(target: $change->target(), doesSaid: $change->does()->saidOnTheScreen());
    }

    /**
     * Each change of one list, with what is said of it.
     *
     * @return list<AChangeAndWhyAsShown>
     */
    private function saidOf(ChangesAndWhy $changes): array
    {
        $shown = [];

        foreach ($changes as $change) {
            $shown[] = $this->said($change);
        }

        return $shown;
    }

    /** One change, and what is said of it. */
    private function said(AChangeAndWhy $change): AChangeAndWhyAsShown
    {
        return new AChangeAndWhyAsShown(target: $change->target(), because: $change->because());
    }

    /** A record that did not come back, or a run it could not be asked about, with nothing in it. */
    private function nothingShown(HowTheReadingWent $went, bool $namesARun = true): WhatPuttingARunBackWouldShow
    {
        return new WhatPuttingARunBackWouldShow(
            went: $went,
            namesARun: $namesARun,
            isOnTheRecord: false,
            goesBack: false,
            whenSaid: '',
            whenCount: 0,
            alongside: 0,
            changes: [],
        );
    }

    /** A state with no report to draw. */
    private function following(
        HowTheReadingWent $went,
        bool $isWorking = false,
        bool $hasEnded = false,
        ?ARefusalAsShown $refused = null,
    ): HowPuttingARunBackWent {
        return new HowPuttingARunBackWent(
            went: $went,
            isWorking: $isWorking,
            hasEnded: $hasEnded,
            hasReport: false,
            rehearsed: false,
            leftNothing: false,
            headline: '',
            reversedSaid: '',
            noneReversed: '',
            reversed: [],
            left: [],
            noted: [],
            refused: $refused,
        );
    }
}
