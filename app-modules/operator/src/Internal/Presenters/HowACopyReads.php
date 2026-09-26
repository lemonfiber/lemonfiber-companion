<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ACopyTaken;
use Modules\Kernel\Api\HowACopyPaced;
use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\WhetherItHoldsASecret;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Operator\Internal\ViewModels\APaceAsShown;
use Modules\Operator\Internal\ViewModels\ASizeAsShown;
use Modules\Operator\Internal\ViewModels\HowTheCopyWent;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;

/**
 * What became of a copy asked for, as the template draws it.
 *
 * One method per state of following it, each saying only its own state. A
 * rehearsal's report is worded as what would happen, in every sentence, and
 * never as what did.
 */
final readonly class HowACopyReads
{
    /** Nothing was asked for from this screen, so there is nothing to follow. */
    public function notAsked(): HowTheCopyWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), wasAsked: false);
    }

    /** This device no longer holds a session for the stack the copy was asked of. */
    public function signedOut(): HowTheCopyWent
    {
        return $this->following(HowTheReadingWent::theSessionEnded());
    }

    /** Asking for it, or asking after it, met this instead. */
    public function met(Obstacle $why): HowTheCopyWent
    {
        return $this->following(HowTheReadingWent::somethingStopped($why));
    }

    /** The stack is still taking the copy asked for. */
    public function running(ScopeOfACopy $asked): HowTheCopyWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), isWorking: true, scope: $asked);
    }

    /** The stack has no outcome for it any more, which is not the same as it failing. */
    public function ended(ScopeOfACopy $asked): HowTheCopyWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), hasEnded: true, scope: $asked);
    }

    /** The stack's report of the copy, in the tense it allows. */
    public function done(ACopyTaken $report): HowTheCopyWent
    {
        $rehearsed = $report->was() === WhetherItWasRehearsed::Rehearsed;

        return new HowTheCopyWent(
            went: HowTheReadingWent::itCameBack(),
            wasAsked: true,
            isWorking: false,
            hasEnded: false,
            wasRehearsed: $rehearsed,
            scope: new HowAScopeReads()->of($report->scope()),
            tookSaid: $rehearsed ? 'stacks.copy.would_copy' : 'stacks.copy.copied',
            pruned: [...$report->pruned()],
            prunedSaid: $rehearsed ? 'stacks.copy.would_remove' : 'stacks.copy.removed',
            pace: $this->pace($report->pace(), $rehearsed),
            holdsSaid: $this->holds($report->holds(), $rehearsed),
        );
    }

    /** Whether the copy holds credentials, in the tense the report allows. */
    private function holds(WhetherItHoldsASecret $holds, bool $rehearsed): string
    {
        $secret = $holds === WhetherItHoldsASecret::Secret;

        return match (true) {
            $rehearsed && $secret => 'stacks.copy.would_hold_credentials',
            $rehearsed => 'stacks.copy.would_hold_no_credentials',
            $secret => 'stacks.copy.holds_credentials',
            default => 'stacks.copy.holds_no_credentials',
        };
    }

    /** What the copy came to against the budget, in the tense the report allows. */
    private function pace(HowACopyPaced $pace, bool $rehearsed): APaceAsShown
    {
        return new APaceAsShown(
            said: match (true) {
                $rehearsed && $pace->isBrisk() => 'stacks.copy.pace.would_be_brisk',
                $rehearsed => 'stacks.copy.pace.would_be_slow',
                $pace->isBrisk() => 'stacks.copy.pace.brisk',
                default => 'stacks.copy.pace.slow',
            },
            moved: $this->size($pace->moved()),
            budget: $this->size($pace->budget()),
        );
    }

    /** Bytes, as a figure and a unit. */
    private function size(int $bytes): ASizeAsShown
    {
        $big = HowBig::of($bytes);

        return new ASizeAsShown(figure: $big->figure, unit: $big->said);
    }

    /** A state with no report to draw. */
    private function following(
        HowTheReadingWent $went,
        bool $wasAsked = true,
        bool $isWorking = false,
        bool $hasEnded = false,
        ?ScopeOfACopy $scope = null,
    ): HowTheCopyWent {
        return new HowTheCopyWent(
            went: $went,
            wasAsked: $wasAsked,
            isWorking: $isWorking,
            hasEnded: $hasEnded,
            wasRehearsed: false,
            scope: $scope instanceof ScopeOfACopy ? new HowAScopeReads()->of($scope) : null,
            tookSaid: null,
            pruned: [],
            prunedSaid: null,
            pace: null,
            holdsSaid: null,
        );
    }
}
