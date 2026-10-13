<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\WhereAChoiceStands;
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

        return $this->without(HowTheReadingWent::itCameBack());
    }

    public function met(Obstacle $why): TheWarningTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::somethingStopped($why));
    }

    public function signedOut(): TheWarningTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::theSessionEnded());
    }

    private function of(Finding $finding): TheWarningTurnedOutToBe
    {
        $stands = WhereAChoiceStands::of($finding);

        return $finding->said()->either(
            nothingWrong: fn(): TheWarningTurnedOutToBe => $this->without(HowTheReadingWent::itCameBack()),
            wentWrong: fn(Code $code, string $meaning, Remedies $remedies): TheWarningTurnedOutToBe => $stands === WhereAChoiceStands::NotAChoice
                ? $this->without(HowTheReadingWent::itCameBack())
                : new TheWarningTurnedOutToBe(
                    went: HowTheReadingWent::itCameBack(),
                    title: $finding->title(),
                    meaning: $meaning,
                    remedies: $remedies,
                    check: $stands === WhereAChoiceStands::Unanswered ? $finding->check()->shown() : '',
                    accepted: $stands === WhereAChoiceStands::Accepted,
                ),
            couldNotSay: fn(): TheWarningTurnedOutToBe => $this->without(HowTheReadingWent::itCameBack()),
        );
    }

    private function without(HowTheReadingWent $went): TheWarningTurnedOutToBe
    {
        return new TheWarningTurnedOutToBe(went: $went, title: '', meaning: '', remedies: Remedies::none(), check: '', accepted: false);
    }
}
