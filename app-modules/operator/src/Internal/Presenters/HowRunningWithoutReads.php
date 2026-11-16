<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\HowItRunsWithoutLemonfiber;
use Modules\Kernel\Api\Obstacle;
use Modules\Operator\Internal\ViewModels\APartLeftOutAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\RunningWithoutAsShown;

/**
 * Turns how a stack runs without lemonfiber into what the stack's settings and its home draw.
 */
final readonly class HowRunningWithoutReads
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): RunningWithoutAsShown
    {
        return $this->nothing(HowTheReadingWent::theSessionEnded());
    }

    /** It did not answer, and this is what the operator met. */
    public function met(Obstacle $why): RunningWithoutAsShown
    {
        return $this->nothing(HowTheReadingWent::somethingStopped($why));
    }

    /** The stack said how it runs without lemonfiber. */
    public function this(HowItRunsWithoutLemonfiber $how): RunningWithoutAsShown
    {
        $leftOut = [];

        foreach ($how->leftOut() as $part) {
            $leftOut[] = new APartLeftOutAsShown($part->part(), $part->reason());
        }

        return new RunningWithoutAsShown(
            went: HowTheReadingWent::itCameBack(),
            ownDirectory: $how->whose()->directory(),
            project: $how->project(),
            env: $how->env(),
            plainUp: $how->plainUp()->asTyped(),
            nonInteractive: $how->nonInteractive(),
            leftOut: $leftOut,
        );
    }

    private function nothing(HowTheReadingWent $went): RunningWithoutAsShown
    {
        return new RunningWithoutAsShown($went, '', '', '', '', '', []);
    }
}
