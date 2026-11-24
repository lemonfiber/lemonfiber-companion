<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\Accepting;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Asking;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HearingTheJobs;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\FollowsOneJobAtATime;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowAWarningReads;
use Modules\Operator\Internal\Presenters\HowTheAcceptanceReads;
use Modules\Operator\Internal\ViewModels\TheAcceptanceTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheWarningTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * What accepting one warning about a choice costs, in the stack's words, and accepting it on a separate yes.
 *
 * The check the yes names is the one the stack's own report warns about, never the route's: a check the report
 * does not warn about, or a failure, is shown and not offered.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatAcceptingItCosts extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use DrawsItsTemplate;

    /** @use FollowsOneJobAtATime<TheAcceptanceTurnedOutToBe> */
    use FollowsOneJobAtATime, FindsItsWayAround {
        FollowsOneJobAtATime::letGoOfWhatElseItHears insteadof FindsItsWayAround;
    }

    public const string TEMPLATE = 'operator::what-accepting-it-costs';

    /** The warning as the stack last raised it, once the frame has asked. */
    public ?TheWarningTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Asking $asking,
        private readonly Accepting $answering,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        protected readonly HearingTheJobs $hearingTheJobs,
    ) {}

    public function again(): void
    {
        $this->answered = null;
    }

    public function accept(): void
    {
        $check = $this->answer()->raised?->check;

        if ($check === null || $this->awaitsAnOutcome()) {
            return;
        }

        $this->forget();
        $this->reported($this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->answering->accept($stack, $session, Check::of($check))));
    }

    /** The warning as the stack last raised it, asked once per frame. */
    public function answer(): TheWarningTurnedOutToBe
    {
        $this->howItIsGoing();
        $stack = $this->stack();
        $named = $this->around->aboutOn($this);

        return $this->answered ??= $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheWarningTurnedOutToBe => $this->asking->about($stack, $session)->either(
                said: static fn(Report $report): TheWarningTurnedOutToBe => new HowAWarningReads()->in($report, $named),
                met: function (Obstacle $why) use ($stack): TheWarningTurnedOutToBe {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowAWarningReads()->met($why);
                },
            ),
            notHeld: static fn(): TheWarningTurnedOutToBe => new HowAWarningReads()->signedOut(),
        );
    }

    public function howItIsGoing(): TheAcceptanceTurnedOutToBe
    {
        return $this->whatBecameOfTheJob();
    }

    protected function whatTheJobCameTo(Stack $stack, Session $session, Job $job): TheAcceptanceTurnedOutToBe
    {
        return $this->shown($this->answering->whatBecameOf($stack, $session, $job), $stack);
    }

    protected function whileNoJobIsFollowed(): TheAcceptanceTurnedOutToBe
    {
        return new HowTheAcceptanceReads()->notAsked();
    }

    protected function withNoSessionHeld(): TheAcceptanceTurnedOutToBe
    {
        return new HowTheAcceptanceReads()->signedOut();
    }

    /** @param Closure(Stack, Session): WhatBecameOfTheWork<Report> $asking */
    private function put(Closure $asking): TheAcceptanceTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheAcceptanceTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: static fn(): TheAcceptanceTurnedOutToBe => new HowTheAcceptanceReads()->signedOut(),
        );
    }

    /**
     * @param WhatBecameOfTheWork<Report> $became
     */
    private function shown(WhatBecameOfTheWork $became, Stack $stack): TheAcceptanceTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheAcceptanceTurnedOutToBe {
                $this->follow($job);

                return new HowTheAcceptanceReads()->running();
            },
            answered: function (Report $report): TheAcceptanceTurnedOutToBe {
                $check = $this->around->aboutOn($this);
                $this->answered = new HowAWarningReads()->in($report, $check);

                return new HowTheAcceptanceReads()->answered($report, $check);
            },
            ended: static fn(?WhyTheWorkEnded $why): TheAcceptanceTurnedOutToBe => new HowTheAcceptanceReads()->ended($why),
            refused: static fn(ARefusalInItsWords $why): TheAcceptanceTurnedOutToBe => new HowTheAcceptanceReads()->refused($why),
            met: function (Obstacle $why) use ($stack): TheAcceptanceTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheAcceptanceReads()->met($why);
            },
        );
    }
}
