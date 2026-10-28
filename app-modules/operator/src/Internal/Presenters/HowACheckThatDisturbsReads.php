<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ACheckThatDisturbs;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Report;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\ARunThatDisturbs;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheCheckAsFound;
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

        return new TheCheckTurnedOutToBe(HowTheReadingWent::itCameBack());
    }

    public function met(Obstacle $why): TheCheckTurnedOutToBe
    {
        return new TheCheckTurnedOutToBe(HowTheReadingWent::somethingStopped($why));
    }

    public function signedOut(): TheCheckTurnedOutToBe
    {
        return new TheCheckTurnedOutToBe(HowTheReadingWent::theSessionEnded());
    }

    private function of(Finding $finding, Report $report): TheCheckTurnedOutToBe
    {
        $card = new HowAFindingReads($report->findings())->of($finding);
        $disturbs = $finding->whetherCheckingDisturbs()->either(
            so: static function (ACheckThatDisturbs $disturbing) use ($finding): ARunThatDisturbs {
                $bound = new HowABoundReads()->of($disturbing);

                return new ARunThatDisturbs($disturbing->disturbs(), $bound->said, $bound->seconds, $finding->check()->shown(), $disturbing->narrowedTo());
            },
            not: static fn(): AsText => AsText::nothing(),
        );

        return new TheCheckTurnedOutToBe(
            HowTheReadingWent::itCameBack(),
            new TheCheckAsFound($card->title, $card->verdict, $card->meaning, $disturbs instanceof ARunThatDisturbs ? $disturbs : null),
        );
    }
}
