<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AWalkthrough;
use Modules\Kernel\Api\HowTheImportLinked;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WalkthroughStep;
use Modules\Kernel\Api\WhatToDoNext;
use Modules\Kernel\Api\WhereItStopped;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\AStepOnAsShown;
use Modules\Operator\Internal\ViewModels\AWalkthroughLineAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheWalkthroughAsRecorded;
use Modules\Operator\Internal\ViewModels\WhatTheWalkthroughTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhereItStoppedAsShown;

/**
 * What following a walkthrough produces, as the fields a screen draws.
 *
 * `F2`: data in, view model out. One method per state of following it, each
 * saying only its own state. The lines are handed on in the order the stack
 * said them and with the stack's own words, never regrouped or rephrased.
 */
final readonly class HowAWalkthroughReads
{
    /**
     * Nothing was started from this screen, so there is nothing to follow; only the road a walk takes.
     *
     * The steps in the order the contract declares them, which is the order
     * a walk takes them, each the stack's own word.
     */
    public function notStarted(): WhatTheWalkthroughTurnedOutToBe
    {
        $road = [];

        foreach (WalkthroughStep::cases() as $step) {
            $road[] = $step->value;
        }

        return new WhatTheWalkthroughTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            wasStarted: false,
            isWorking: false,
            hasEnded: false,
            record: null,
            road: $road,
        );
    }

    /** This device no longer holds a session for the stack. */
    public function signedOut(): WhatTheWalkthroughTurnedOutToBe
    {
        return $this->following(HowTheReadingWent::theSessionEnded());
    }

    /** The stack is still walking it. */
    public function running(): WhatTheWalkthroughTurnedOutToBe
    {
        return $this->following(HowTheReadingWent::itCameBack(), isWorking: true);
    }

    /** The stack has no outcome for it any more, which is not the same as it failing. */
    public function ended(): WhatTheWalkthroughTurnedOutToBe
    {
        return $this->following(HowTheReadingWent::itCameBack(), hasEnded: true);
    }

    /** Starting it, or asking after it, met this instead. */
    public function met(Obstacle $why): WhatTheWalkthroughTurnedOutToBe
    {
        return $this->following(HowTheReadingWent::somethingStopped($why));
    }

    /** The walkthrough's own record, once it finished. */
    public function done(AWalkthrough $report): WhatTheWalkthroughTurnedOutToBe
    {
        $lines = [];

        foreach ($report->lines() as $line) {
            $lines[] = new AWalkthroughLineAsShown($line->step()->value, $line->said(), $line->detail(
                said: static fn(string $detail): AsText => AsText::of($detail),
                nothing: static fn(): AsText => AsText::nothing(),
            )->said);
        }

        $suggestions = [];

        foreach ($report->suggestions() as $suggestion) {
            $suggestions[] = $suggestion;
        }

        $next = [];

        foreach ($report->handover() as $step) {
            $next[] = new AStepOnAsShown($step->saidOnTheScreen(), leadsToWhereToWatch: $step === WhatToDoNext::ClientApps);
        }

        return new WhatTheWalkthroughTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            wasStarted: true,
            isWorking: false,
            hasEnded: false,
            record: new TheWalkthroughAsRecorded(
                item: $report->item()->either(
                    named: static fn(string $item): AsText => AsText::of($item),
                    nothingChosen: static fn(): AsText => AsText::nothing(),
                )->said,
                alreadyHere: $report->wasAlreadyHere(),
                shapeSaid: $report->shape()->saidOnTheScreen(),
                stateSaid: $report->state()->saidOnTheScreen(),
                proves: $report->proves(),
                lines: $lines,
                suggestions: $suggestions,
                inBackground: $report->wentOnInTheBackground(),
                linkSaid: $report->link(
                    linked: static fn(HowTheImportLinked $link): AsText => AsText::of($link->saidOnTheScreen()),
                    notImported: static fn(): AsText => AsText::nothing(),
                )->said,
                next: $next,
                stopped: $report->stopped(
                    at: static function (WhereItStopped $stopped): WhereItStoppedAsShown {
                        $logs = [];

                        foreach ($stopped->logs() as $log) {
                            $logs[] = $log;
                        }

                        return new WhereItStoppedAsShown(
                            didStop: true,
                            step: $stopped->step()->value,
                            whySaid: $stopped->why()->saidOnTheScreen(),
                            remedy: $stopped->remedy(),
                            logs: $logs,
                        );
                    },
                    didNotStop: static fn(): WhereItStoppedAsShown => WhereItStoppedAsShown::nowhere(),
                ),
            ),
            road: [],
        );
    }

    /** A state with no record in it. */
    private function following(
        HowTheReadingWent $went,
        bool $isWorking = false,
        bool $hasEnded = false,
    ): WhatTheWalkthroughTurnedOutToBe {
        return new WhatTheWalkthroughTurnedOutToBe(
            went: $went,
            wasStarted: true,
            isWorking: $isWorking,
            hasEnded: $hasEnded,
            record: null,
            road: [],
        );
    }
}
