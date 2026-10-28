<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\WhereAChoiceStands;
use Modules\Operator\Internal\ViewModels\AWarningAsRaised;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheWarningTurnedOutToBe;

/**
 * One warning about a choice as the stack last raised it, as the template draws it before it is accepted.
 */
final readonly class HowAWarningReads
{
    /** The finding about this check in what the stack reported, and whether it may be accepted. */
    public function in(Report $report, string $check): TheWarningTurnedOutToBe
    {
        foreach ($report->findings() as $finding) {
            if ($finding->check()->shown() === $check) {
                return $this->of($finding);
            }
        }

        return new TheWarningTurnedOutToBe(HowTheReadingWent::itCameBack());
    }

    public function met(Obstacle $why): TheWarningTurnedOutToBe
    {
        return new TheWarningTurnedOutToBe(HowTheReadingWent::somethingStopped($why));
    }

    public function signedOut(): TheWarningTurnedOutToBe
    {
        return new TheWarningTurnedOutToBe(HowTheReadingWent::theSessionEnded());
    }

    private function of(Finding $finding): TheWarningTurnedOutToBe
    {
        return $finding->said()->either(
            nothingWrong: fn(): TheWarningTurnedOutToBe => new TheWarningTurnedOutToBe(HowTheReadingWent::itCameBack()),
            wentWrong: fn(Code $code, string $meaning, Remedies $remedies): TheWarningTurnedOutToBe => $this->raised($finding, $meaning, $remedies),
            couldNotSay: fn(): TheWarningTurnedOutToBe => new TheWarningTurnedOutToBe(HowTheReadingWent::itCameBack()),
        );
    }

    /** A finding that went wrong, drawn as a choice only where it is one, and offered only where it is unanswered. */
    private function raised(Finding $finding, string $meaning, Remedies $remedies): TheWarningTurnedOutToBe
    {
        $stands = WhereAChoiceStands::of($finding);

        if ($stands === WhereAChoiceStands::NotAChoice) {
            return new TheWarningTurnedOutToBe(HowTheReadingWent::itCameBack());
        }

        return new TheWarningTurnedOutToBe(
            HowTheReadingWent::itCameBack(),
            new AWarningAsRaised($finding->title(), $meaning, $remedies, $stands === WhereAChoiceStands::Unanswered ? $finding->check()->shown() : null),
        );
    }
}
