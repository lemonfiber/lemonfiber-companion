<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\AnAllowanceChange;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ChoosingWhatTheHouseholdMayAsk;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Inviting;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Owing;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAllowanceAsChanged;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheMembers;
use Modules\Kernel\Api\WhatBecameOfTheAllowanceChange;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\ChoosesWhatIsAllowed;
use Modules\Operator\Internal\LooksAgainWhileOpen;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheAllowanceChangeReads;
use Modules\Operator\Internal\Presenters\HowTheAllowanceChoicesRead;
use Modules\Operator\Internal\Presenters\HowTheAllowanceReads;
use Modules\Operator\Internal\ViewModels\TheAllowanceChangeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheAllowanceTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhoToChooseAsShown;
use Modules\Operator\Internal\WhatTheAllowanceChangeIsAskedWith;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * What this machine says the household may ask it for, and changing it, on the operator's side.
 *
 * The household's own reading, in the core's words, and beside it a change for the whole household or one member:
 * rehearsed first and drawn as a rehearsal, made only on a separate yes.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatTheHouseholdIsAllowed extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use LooksAgainWhileOpen;
    use OffersTheAppsSettings;
    use ChoosesWhatIsAllowed;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::what-the-household-is-allowed';

    /** What came back, once the frame has asked. */
    public ?TheAllowanceTurnedOutToBe $answered = null;

    /** Each member a change can be about, once a frame of its own has asked. */
    public ?WhoToChooseAsShown $household = null;

    /** The change the rehearsal on the screen was asked with, which is the one a yes sends. */
    public ?AnAllowanceChange $asked = null;

    /** Whether the report on the screen is a rehearsal a yes may be given to. */
    public bool $rehearsed = false;

    /** The handle of the work being followed, where there is one. */
    public ?string $following = null;

    public ?TheAllowanceChangeTurnedOutToBe $going = null;

    public function __construct(
        private readonly Owing $owing,
        private readonly ChoosingWhatTheHouseholdMayAsk $choosing,
        private readonly Inviting $inviting,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    public function again(): void
    {
        $this->answered = null;
    }

    /** Make the change about one member, offering each by name. */
    public function forAMember(): void
    {
        $this->choosingAMember = true;
        $this->household = null;
    }

    public function startAgain(): void
    {
        $this->asked = null;
        $this->rehearsed = false;
        $this->following = null;
        $this->going = null;
    }

    public function rehearse(): void
    {
        $this->startAgain();

        $this->going = WhatTheAllowanceChangeIsAskedWith::from($this->who, $this->policy, $this->requests, $this->days)->either(
            asked: fn(AnAllowanceChange $change): TheAllowanceChangeTurnedOutToBe => $this->put(function (Stack $stack, Session $session) use ($change): WhatBecameOfTheAllowanceChange {
                $this->asked = $change;

                return $this->choosing->rehearse($stack, $session, $change);
            }),
            notAskable: static fn(string $why): TheAllowanceChangeTurnedOutToBe => new HowTheAllowanceChangeReads()->notAskable($why),
        );
    }

    public function apply(): void
    {
        $change = $this->asked;

        if (! $change instanceof AnAllowanceChange || ! $this->rehearsed) {
            return;
        }

        $this->rehearsed = false;
        $this->going = $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheAllowanceChange => $this->choosing->carryOut($stack, $session, $change));
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

    /** What the stack said, asked once per frame. */
    public function answer(): TheAllowanceTurnedOutToBe
    {
        $this->howItIsGoing();
        $stack = $this->stack();

        return $this->answered ??= $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheAllowanceTurnedOutToBe => $this->owing->toHandOver($stack, $session)->either(
                told: static fn(Sentences $said): TheAllowanceTurnedOutToBe => new HowTheAllowanceReads()->these($said),
                refused: function (Obstacle $why) use ($stack): TheAllowanceTurnedOutToBe {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowTheAllowanceReads()->met($why);
                },
            ),
            notHeld: static fn(): TheAllowanceTurnedOutToBe => new HowTheAllowanceReads()->signedOut(),
        );
    }

    /** Each member a change can be about, asked once per frame. */
    public function whoMayBeChosen(): WhoToChooseAsShown
    {
        $stack = $this->stack();

        return $this->household ??= $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhoToChooseAsShown => $this->inviting->whoIsIn($stack, $session)->either(
                found: static fn(TheMembers $members): WhoToChooseAsShown => new HowTheAllowanceChoicesRead()->members($members),
                met: static fn(): WhoToChooseAsShown => new HowTheAllowanceChoicesRead()->onlyEverybody(),
            ),
            notHeld: static fn(): WhoToChooseAsShown => new HowTheAllowanceChoicesRead()->onlyEverybody(),
        );
    }

    public function howItIsGoing(): TheAllowanceChangeTurnedOutToBe
    {
        $following = $this->following;

        return $this->going ??= $following === null
            ? new HowTheAllowanceChangeReads()->notAsked()
            : $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheAllowanceChange => $this->choosing->whatBecameOf($stack, $session, Job::named($following)));
    }

    /** @param Closure(Stack, Session): WhatBecameOfTheAllowanceChange $asking */
    private function put(Closure $asking): TheAllowanceChangeTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheAllowanceChangeTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: static fn(): TheAllowanceChangeTurnedOutToBe => new HowTheAllowanceChangeReads()->signedOut(),
        );
    }

    private function shown(WhatBecameOfTheAllowanceChange $became, Stack $stack): TheAllowanceChangeTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheAllowanceChangeTurnedOutToBe {
                $this->following = $job->shown();

                return new HowTheAllowanceChangeReads()->running();
            },
            answered: function (TheAllowanceAsChanged $report): TheAllowanceChangeTurnedOutToBe {
                $this->following = null;
                $this->rehearsed = $report->did() === WhetherItWasRehearsed::Rehearsed;

                if (! $this->rehearsed) {
                    $this->answered = null;
                }

                return new HowTheAllowanceChangeReads()->answered($report);
            },
            ended: function (): TheAllowanceChangeTurnedOutToBe {
                $this->following = null;

                return new HowTheAllowanceChangeReads()->ended();
            },
            refused: function (ARefusalInItsWords $why): TheAllowanceChangeTurnedOutToBe {
                $this->following = null;

                return new HowTheAllowanceChangeReads()->refused($why);
            },
            met: function (Obstacle $why) use ($stack): TheAllowanceChangeTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheAllowanceChangeReads()->met($why);
            },
        );
    }
}
