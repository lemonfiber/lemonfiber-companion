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
use Modules\Kernel\Api\HearingTheJobs;
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
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\ChoosesWhatIsAllowed;
use Modules\Operator\Internal\FollowsOneJobAtATime;
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
    use DrawsItsTemplate;
    /** @use FollowsOneJobAtATime<TheAllowanceChangeTurnedOutToBe> */
    use FollowsOneJobAtATime, FindsItsWayAround {
        FollowsOneJobAtATime::letGoOfWhatElseItHears insteadof FindsItsWayAround;
    }

    public const string TEMPLATE = 'operator::what-the-household-is-allowed';

    /** What came back, once the frame has asked. */
    public ?TheAllowanceTurnedOutToBe $answered = null;

    /** Each member a change can be about, once a frame of its own has asked. */
    public ?WhoToChooseAsShown $household = null;

    /** The change the last rehearsal was asked with. */
    public ?AnAllowanceChange $asked = null;

    /** The change a rehearsal on the screen came back for, which is the one a yes sends. */
    public ?AnAllowanceChange $rehearsed = null;

    public function __construct(
        private readonly Owing $owing,
        private readonly ChoosingWhatTheHouseholdMayAsk $choosing,
        private readonly Inviting $inviting,
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

    /** Make the change about one member, offering each by name. */
    public function forAMember(): void
    {
        $this->choosingAMember = true;
        $this->household = null;
    }

    public function startAgain(): void
    {
        $this->asked = null;
        $this->rehearsed = null;
        $this->forget();
    }

    public function rehearse(): void
    {
        $this->startAgain();

        $this->reported(WhatTheAllowanceChangeIsAskedWith::from($this->who, $this->policy, $this->requests, $this->days)->either(
            asked: fn(AnAllowanceChange $change): TheAllowanceChangeTurnedOutToBe => $this->put(function (Stack $stack, Session $session) use ($change): WhatBecameOfTheWork {
                $this->asked = $change;

                return $this->choosing->rehearse($stack, $session, $change);
            }),
            notAskable: static fn(string $why): TheAllowanceChangeTurnedOutToBe => new HowTheAllowanceChangeReads()->notAskable($why),
        ));
    }

    public function apply(): void
    {
        $change = $this->rehearsed;

        if (! $change instanceof AnAllowanceChange) {
            return;
        }

        $this->rehearsed = null;
        $this->forget();
        $this->reported($this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->choosing->carryOut($stack, $session, $change)));
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
        return $this->whatBecameOfTheJob();
    }

    protected function whatTheJobCameTo(Stack $stack, Session $session, Job $job): TheAllowanceChangeTurnedOutToBe
    {
        return $this->shown($this->choosing->whatBecameOf($stack, $session, $job), $stack);
    }

    protected function whileNoJobIsFollowed(): TheAllowanceChangeTurnedOutToBe
    {
        return new HowTheAllowanceChangeReads()->notAsked();
    }

    protected function withNoSessionHeld(): TheAllowanceChangeTurnedOutToBe
    {
        return new HowTheAllowanceChangeReads()->signedOut();
    }

    /** @param Closure(Stack, Session): WhatBecameOfTheWork<TheAllowanceAsChanged> $asking */
    private function put(Closure $asking): TheAllowanceChangeTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheAllowanceChangeTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: static fn(): TheAllowanceChangeTurnedOutToBe => new HowTheAllowanceChangeReads()->signedOut(),
        );
    }

    /**
     * @param WhatBecameOfTheWork<TheAllowanceAsChanged> $became
     */
    private function shown(WhatBecameOfTheWork $became, Stack $stack): TheAllowanceChangeTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheAllowanceChangeTurnedOutToBe {
                $this->follow($job);

                return new HowTheAllowanceChangeReads()->running();
            },
            answered: function (TheAllowanceAsChanged $report): TheAllowanceChangeTurnedOutToBe {
                $this->rehearsed = $report->did() === WhetherItWasRehearsed::Rehearsed ? $this->asked : null;

                if (!$this->rehearsed instanceof AnAllowanceChange) {
                    $this->answered = null;
                }

                return new HowTheAllowanceChangeReads()->answered($report);
            },
            ended: static fn(?WhyTheWorkEnded $why): TheAllowanceChangeTurnedOutToBe => new HowTheAllowanceChangeReads()->ended($why),
            refused: static fn(ARefusalInItsWords $why): TheAllowanceChangeTurnedOutToBe => new HowTheAllowanceChangeReads()->refused($why),
            met: function (Obstacle $why) use ($stack): TheAllowanceChangeTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheAllowanceChangeReads()->met($why);
            },
        );
    }
}
