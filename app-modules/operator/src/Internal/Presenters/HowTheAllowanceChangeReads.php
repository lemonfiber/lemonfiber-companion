<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheAllowanceAsChanged;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheAllowanceChangeAsShown;
use Modules\Operator\Internal\ViewModels\TheAllowanceChangeTurnedOutToBe;

/**
 * Where changing what the household may ask for has got to, as the template draws it.
 */
final readonly class HowTheAllowanceChangeReads
{
    /** What a rehearsal is headed with. */
    private const string REHEARSED = 'stacks.allowance_change.did.rehearsed';

    /** What a change that was made is headed with. */
    private const string CARRIED_OUT = 'stacks.allowance_change.did.carried_out';

    /** Nothing has been asked yet. */
    public function notAsked(): TheAllowanceChangeTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack());
    }

    /** What was chosen was not sent, and why. */
    public function notAskable(string $why): TheAllowanceChangeTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), notAskable: $why);
    }

    /** This phone holds no session for the stack. */
    public function signedOut(): TheAllowanceChangeTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::theSessionEnded());
    }

    /** The stack is still at it. */
    public function running(): TheAllowanceChangeTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), isWorking: true);
    }

    /** The stack has no outcome for it any more. */
    public function ended(): TheAllowanceChangeTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), hasEnded: true);
    }

    /** The stack refused it, in its own words. */
    public function refused(ARefusalInItsWords $why): TheAllowanceChangeTurnedOutToBe
    {
        return new TheAllowanceChangeTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            isWorking: false,
            hasEnded: false,
            notAskable: '',
            refused: new HowARefusalReads()->inItsWords($why),
            change: null,
        );
    }

    /** What stood in the way of asking. */
    public function met(Obstacle $why): TheAllowanceChangeTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::somethingStopped($why));
    }

    /** What the run reported, headed by whether it was a rehearsal. */
    public function answered(TheAllowanceAsChanged $report): TheAllowanceChangeTurnedOutToBe
    {
        $sentences = [];

        foreach ($report->whatItSays() as $sentence) {
            $sentences[] = $sentence->shown();
        }

        $rehearsed = $report->did() === WhetherItWasRehearsed::Rehearsed;

        return new TheAllowanceChangeTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            isWorking: false,
            hasEnded: false,
            notAskable: '',
            refused: null,
            change: new TheAllowanceChangeAsShown(
                didSaid: $rehearsed ? self::REHEARSED : self::CARRIED_OUT,
                mayBeApplied: $rehearsed,
                sentences: $sentences,
            ),
        );
    }

    private function without(HowTheReadingWent $went, bool $isWorking = false, bool $hasEnded = false, string $notAskable = ''): TheAllowanceChangeTurnedOutToBe
    {
        return new TheAllowanceChangeTurnedOutToBe(
            went: $went,
            isWorking: $isWorking,
            hasEnded: $hasEnded,
            notAskable: $notAskable,
            refused: null,
            change: null,
        );
    }
}
