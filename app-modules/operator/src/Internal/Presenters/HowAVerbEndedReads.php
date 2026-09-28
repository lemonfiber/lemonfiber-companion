<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\WhatTheVerbCameTo;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhereTheServicesEndedUp;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\APortHeldAsShown;
use Modules\Operator\Internal\ViewModels\AServiceNotBackAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\HowTheVerbWent;

use function sprintf;

/**
 * What became of a verb sent from the screen about one thing, as the template draws it.
 *
 * One method per state of following it, each saying only its own state.
 *
 * **A verb that brings services up is complete only where the report says
 * so**, which is {@see WhatTheVerbCameTo::broughtEverythingBack()}'s decision.
 * Anything short of it is said as not having brought everything back, with
 * every service short of running named beside its state. A stop is not judged
 * that way: leaving services down is what it was for.
 *
 * **A rehearsal is worded as what would happen**, in every sentence, and
 * what did not come back is not named for one, because nothing was brought
 * up to come back.
 */
final readonly class HowAVerbEndedReads
{
    /** Nothing was sent from this screen, so there is nothing to follow. */
    public function notAsked(): HowTheVerbWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), wasAsked: false);
    }

    /** This device no longer holds a session for the stack the verb was sent to. */
    public function signedOut(): HowTheVerbWent
    {
        return $this->following(HowTheReadingWent::theSessionEnded());
    }

    /** Sending it, or asking after it, met this instead. */
    public function met(Obstacle $why): HowTheVerbWent
    {
        return $this->following(HowTheReadingWent::somethingStopped($why));
    }

    /** The stack is still carrying it out. */
    public function running(): HowTheVerbWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), isWorking: true);
    }

    /** The stack has no outcome for it any more, which is not the same as it failing. */
    public function ended(): HowTheVerbWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), hasEnded: true);
    }

    /** The stack's report of the verb, judged against what the verb was for. */
    public function done(WhatTheVerbCameTo $report, WhatToDoWithIt $verb): HowTheVerbWent
    {
        $rehearsed = $report->was() === WhetherItWasRehearsed::Rehearsed;
        $because = $report->whetherItRan(
            ran: static fn(): AsText => AsText::nothing(),
            declined: static fn(string $why): AsText => AsText::of($why),
        )->said;
        $ran = $because === '';

        return new HowTheVerbWent(
            went: HowTheReadingWent::itCameBack(),
            wasAsked: true,
            isWorking: false,
            hasEnded: false,
            wasRehearsed: $rehearsed,
            cameToSaid: $ran ? $this->cameTo($report, $verb, $rehearsed) : 'health.came_to.declined',
            because: $because,
            amountsToSaid: $report->amountsTo(
                said: static fn(HowTheStackIsRunning $condition): AsText => AsText::of($condition->saidOnTheScreen()),
                unsaid: static fn(): AsText => AsText::of('health.came_to.unsaid'),
            )->said,
            namesWhatDidNotComeBack: $ran && ! $rehearsed && $verb->bringsSomethingUp() && ! $report->broughtEverythingBack(),
            notBack: $this->notBack($report->whatDidNotComeBack()),
            leftOutSaid: $rehearsed ? 'health.came_to.would_be_left_out' : 'health.came_to.left_out',
            leftOut: new HowWhatWasLeftOutReads()->of($report->leftOut()),
            portsHeld: $this->portsHeld($report->portsHeld()),
        );
    }

    /** What a verb that ran came to, in the tense the report allows. */
    private function cameTo(WhatTheVerbCameTo $report, WhatToDoWithIt $verb, bool $rehearsed): string
    {
        return match (true) {
            $rehearsed => 'health.came_to.rehearsed',
            ! $verb->bringsSomethingUp() => 'health.came_to.stopped',
            $report->broughtEverythingBack() => 'health.came_to.everything_back',
            default => 'health.came_to.not_everything_back',
        };
    }

    /** @return list<AServiceNotBackAsShown> */
    private function notBack(WhereTheServicesEndedUp $services): array
    {
        $shown = [];

        foreach ($services as $service) {
            $shown[] = new AServiceNotBackAsShown($service->name(), $service->runs()->saidOnTheScreen());
        }

        return $shown;
    }

    /** @return list<APortHeldAsShown> */
    private function portsHeld(ThePortsHeld $ports): array
    {
        $shown = [];

        foreach ($ports as $held) {
            $shown[] = new APortHeldAsShown(sprintf('%d', $held->port()), $held->wantedBy(), $held->heldBy());
        }

        return $shown;
    }

    /** A state with no report to draw. */
    private function following(
        HowTheReadingWent $went,
        bool $wasAsked = true,
        bool $isWorking = false,
        bool $hasEnded = false,
    ): HowTheVerbWent {
        return new HowTheVerbWent(
            went: $went,
            wasAsked: $wasAsked,
            isWorking: $isWorking,
            hasEnded: $hasEnded,
            wasRehearsed: false,
            cameToSaid: null,
            because: '',
            amountsToSaid: null,
            namesWhatDidNotComeBack: false,
            notBack: [],
            leftOutSaid: null,
            leftOut: [],
            portsHeld: [],
        );
    }
}
