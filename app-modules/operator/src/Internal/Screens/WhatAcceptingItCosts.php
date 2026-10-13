<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\Accepting;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Asking;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatBecameOfTheAcceptance;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AwaitsAnOutcome;
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
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function trim;

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
    use FindsItsWayAround;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::what-accepting-it-costs';

    /** The warning as the stack last raised it, once the frame has asked. */
    public ?TheWarningTurnedOutToBe $answered = null;

    /** The check a yes was sent for, empty until one is. */
    public string $accepting = '';

    /** The handle of the work being followed, where there is one. */
    public ?string $following = null;

    public ?TheAcceptanceTurnedOutToBe $going = null;

    public function __construct(
        private readonly Asking $asking,
        private readonly Accepting $answering,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    public function again(): void
    {
        $this->answered = null;
    }

    public function accept(): void
    {
        $check = $this->answer()->check;

        if ($check === '' || $this->howItIsGoing()->isWorking) {
            return;
        }

        $this->accepting = $check;
        $this->going = $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheAcceptance => $this->answering->accept($stack, $session, Check::of($check)));
    }

    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->howItIsGoing()->isWorking) {
            $this->going = null;
        }
    }

    public function awaitsAnOutcome(): bool
    {
        return $this->howItIsGoing()->isWorking;
    }

    /** The warning as the stack last raised it, asked once per frame. */
    public function answer(): TheWarningTurnedOutToBe
    {
        $this->howItIsGoing();
        $stack = $this->stack();
        $named = $this->named();

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
        $following = $this->following;

        return $this->going ??= $following === null
            ? new HowTheAcceptanceReads()->notAsked()
            : $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheAcceptance => $this->answering->whatBecameOf($stack, $session, Job::named($following)));
    }

    /** The check the route names, which only finds a warning in what the stack reported. */
    private function named(): string
    {
        $named = $this->param('service');

        return is_string($named) ? trim($named) : '';
    }

    /** @param Closure(Stack, Session): WhatBecameOfTheAcceptance $asking */
    private function put(Closure $asking): TheAcceptanceTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheAcceptanceTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: static fn(): TheAcceptanceTurnedOutToBe => new HowTheAcceptanceReads()->signedOut(),
        );
    }

    private function shown(WhatBecameOfTheAcceptance $became, Stack $stack): TheAcceptanceTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheAcceptanceTurnedOutToBe {
                $this->following = $job->shown();

                return new HowTheAcceptanceReads()->running();
            },
            answered: function (Report $report): TheAcceptanceTurnedOutToBe {
                $this->following = null;
                $this->answered = new HowAWarningReads()->in($report, $this->accepting);

                return new HowTheAcceptanceReads()->answered($report, $this->accepting);
            },
            ended: function (): TheAcceptanceTurnedOutToBe {
                $this->following = null;

                return new HowTheAcceptanceReads()->ended();
            },
            refused: function (ARefusalInItsWords $why): TheAcceptanceTurnedOutToBe {
                $this->following = null;

                return new HowTheAcceptanceReads()->refused($why);
            },
            met: function (Obstacle $why) use ($stack): TheAcceptanceTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheAcceptanceReads()->met($why);
            },
        );
    }
}
