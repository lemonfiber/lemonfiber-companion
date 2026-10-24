<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ACheckThatDisturbs;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Report;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheCheckTurnedOutToBe;

/**
 * One finding that waits on a check that disturbs, as the fields a screen draws before and after it runs.
 *
 * `F2`: data in, view model out. The check is the one the stack's report holds,
 * never the route's: a route naming a check the report does not hold, or one
 * that waits on nothing, is drawn and offers nothing to run.
 */
final readonly class HowACheckThatDisturbsReads
{
    /** The finding about this check in what the stack reported, and what running it would disturb. */
    public function in(Report $report, string $check): TheCheckTurnedOutToBe
    {
        foreach ($report->findings() as $finding) {
            if ($finding->check()->shown() === $check) {
                return $this->of($finding, $report);
            }
        }

        return $this->without(HowTheReadingWent::itCameBack());
    }

    public function met(Obstacle $why): TheCheckTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::somethingStopped($why));
    }

    public function signedOut(): TheCheckTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::theSessionEnded());
    }

    private function of(Finding $finding, Report $report): TheCheckTurnedOutToBe
    {
        $card = new HowAFindingReads($report->findings())->of($finding);

        return $finding->whetherCheckingDisturbs()->either(
            so: static function (ACheckThatDisturbs $disturbing) use ($card, $finding): TheCheckTurnedOutToBe {
                $bound = new HowABoundReads()->of($disturbing);

                return new TheCheckTurnedOutToBe(
                    went: HowTheReadingWent::itCameBack(),
                    title: $card->title,
                    verdict: $card->verdict,
                    meaning: $card->meaning,
                    disturbs: $disturbing->disturbs(),
                    boundSaid: $bound->said,
                    seconds: $bound->seconds,
                    check: $finding->check()->shown(),
                    only: $disturbing->narrowedTo(),
                );
            },
            not: static fn(): TheCheckTurnedOutToBe => new TheCheckTurnedOutToBe(
                went: HowTheReadingWent::itCameBack(),
                title: $card->title,
                verdict: $card->verdict,
                meaning: $card->meaning,
                disturbs: '',
                boundSaid: '',
                seconds: null,
                check: '',
                only: '',
            ),
        );
    }

    private function without(HowTheReadingWent $went): TheCheckTurnedOutToBe
    {
        return new TheCheckTurnedOutToBe(went: $went, title: '', verdict: '', meaning: '', disturbs: '', boundSaid: '', seconds: null, check: '', only: '');
    }
}
