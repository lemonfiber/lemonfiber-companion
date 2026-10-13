<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\ALineChange;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Rationing;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SharingTheLine;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheLineAsChanged;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatTheLineRunDid;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\ChoosesHowTheLineIsShared;
use Modules\Operator\Internal\LooksAgainWhileItMoves;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheLineChangeReads;
use Modules\Operator\Internal\Presenters\HowTheLineReads;
use Modules\Operator\Internal\ViewModels\HowTheLineTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheLineChangeTurnedOutToBe;
use Modules\Operator\Internal\WhatTheLineChangeIsAskedWith;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * How this machine shares its line with the household, and changing it.
 *
 * Where the line stands and what that means, each direction's limit, what the
 * line was measured to carry, the household's hours and the monthly cap: the
 * question somebody asks when the house says the internet is slow, or before
 * a month with a cap on it. It reads the clock once per answer so the
 * measurement's age is counted from one moment.
 *
 * **Every limit the stack declares is changed here**, each set beside the
 * figure the line was measured at and the limit in force.
 *
 * **Nothing is written until the operator has seen what it would do.** The
 * first tap rehearses the change, and what each download client would be
 * asked, and the ratio a limit on seeding costs, are drawn labelled as a
 * rehearsal. The yes sends the change the rehearsal was asked with rather
 * than whatever is in the fields now, and what each client then said, an
 * overshoot included, is drawn after.
 *
 * **Every answer is work the stack names and this follows**, at
 * {@see HowOftenAScreenLooks::WHILE_WORK_RUNS_MS} while it runs. A refusal is
 * drawn as the stack's own sentence and remedy.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class HowTheLineIsSharedHere extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use LooksAgainWhileItMoves;
    use OffersTheAppsSettings;
    use ChoosesHowTheLineIsShared;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::how-the-line-is-shared-here';

    /** What the last rehearsal was asked with, which is what a yes sends. */
    public ?ALineChange $asked = null;

    /** What the stack last reported, rehearsed or applied. */
    public ?TheLineAsChanged $report = null;

    /** The handle of the work being followed, while there is one. */
    public ?string $following = null;

    /** Where changing it has got to, once this frame has asked. Public for {@see WhatIsRunningHere::$answered}'s reason. */
    public ?TheLineChangeTurnedOutToBe $going = null;

    /** The line as it stands, once this frame has asked. Public for the same reason. */
    public ?HowTheLineTurnedOutToBe $answered = null;

    public function __construct(
        private readonly SharingTheLine $sharing,
        private readonly Rationing $rationing,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        private readonly Clock $clock,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /**
     * Read the line again.
     *
     * The change keeps what it last came to: the work being followed is asked
     * after on its own cadence, and an answer already drawn stays drawn.
     */
    public function again(): void
    {
        $this->answered = null;
    }

    /** Leave the answer and start over, keeping what was typed. */
    public function startAgain(): void
    {
        $this->asked = null;
        $this->report = null;
        $this->following = null;
        $this->going = null;
    }

    /**
     * Ask the stack what the change would do, writing nothing.
     *
     * What was typed becomes the request here; a change asking nothing, or a
     * lift that is not whole minutes, is said on the screen rather than sent.
     */
    public function rehearse(): void
    {
        $this->startAgain();

        $this->going = WhatTheLineChangeIsAskedWith::from($this->down, $this->up, $this->active, $this->line, $this->cap, $this->exceeded, $this->lift)->either(
            asked: fn(ALineChange $change): TheLineChangeTurnedOutToBe => $this->put(function (Stack $stack, Session $session) use ($change): WhatBecameOfTheWork {
                $this->asked = $change;

                return $this->sharing->rehearse($stack, $session, $change);
            }),
            notAskable: static fn(string $why): TheLineChangeTurnedOutToBe => new HowTheLineChangeReads()->notAskable($why),
        );
    }

    /**
     * Make the change the rehearsal on the screen described.
     *
     * Reached only beneath a rehearsal; a tap from anywhere else does nothing,
     * rather than sending a change nobody was shown.
     */
    public function apply(): void
    {
        $change = $this->asked;

        if (! $change instanceof ALineChange || $this->report?->did() !== WhatTheLineRunDid::Rehearsed) {
            return;
        }

        $this->report = null;
        $this->going = $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->sharing->carryOut($stack, $session, $change));
    }

    /**
     * Ask after the work again while the stack is carrying it out.
     *
     * Nothing happens unless it is running. The interval is {@see HowOftenAScreenLooks}'s.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->howItIsGoing()->isWorking) {
            $this->going = null;
        }
    }

    /** The same question this screen's cadence asks, answered from what it last heard. */
    public function awaitsAnOutcome(): bool
    {
        return $this->howItIsGoing()->isWorking;
    }

    /**
     * The line as it stands, asked once per frame.
     *
     * Asked after where the change has got to, so a change the stack has just
     * made is read in the same frame that says it was made.
     */
    public function answer(): HowTheLineTurnedOutToBe
    {
        $this->howItIsGoing();
        $stack = $this->stack();

        return $this->answered ??= $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheLineTurnedOutToBe => $this->rationing->rationedOn($stack, $session)->either(
                shared: fn(HowTheLineIsShared $line): HowTheLineTurnedOutToBe => new HowTheLineReads()->this($line, $this->clock->now()),
                met: $this->lettingGoIfRefused($stack, new HowTheLineReads()->met(...)),
            ),
            notHeld: static fn(): HowTheLineTurnedOutToBe => new HowTheLineReads()->signedOut(),
        );
    }

    /** Where changing it has got to, asked once per frame. */
    public function howItIsGoing(): TheLineChangeTurnedOutToBe
    {
        $following = $this->following;

        return $this->going ??= $following === null
            ? new HowTheLineChangeReads()->notAsked()
            : $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->sharing->whatBecameOf($stack, $session, Job::named($following)));
    }

    /**
     * One act put to the stack, with the session this device holds for it.
     *
     * @param Closure(Stack, Session): WhatBecameOfTheWork<TheLineAsChanged> $asking
     */
    private function put(Closure $asking): TheLineChangeTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheLineChangeTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: static fn(): TheLineChangeTurnedOutToBe => new HowTheLineChangeReads()->signedOut(),
        );
    }

    /**
     * What the stack said, as the screen draws it, holding what to follow or to agree to.
     *
     * @param WhatBecameOfTheWork<TheLineAsChanged> $became
     */
    private function shown(WhatBecameOfTheWork $became, Stack $stack): TheLineChangeTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheLineChangeTurnedOutToBe {
                $this->following = $job->shown();

                return new HowTheLineChangeReads()->running();
            },
            answered: function (TheLineAsChanged $report): TheLineChangeTurnedOutToBe {
                $this->following = null;
                $this->report = $report;

                if ($report->did() !== WhatTheLineRunDid::Rehearsed) {
                    $this->answered = null;
                }

                return new HowTheLineChangeReads()->answered($report, $this->clock->now());
            },
            ended: function (): TheLineChangeTurnedOutToBe {
                $this->following = null;

                return new HowTheLineChangeReads()->ended();
            },
            refused: function (ARefusalInItsWords $why): TheLineChangeTurnedOutToBe {
                $this->following = null;

                return new HowTheLineChangeReads()->refused($why);
            },
            met: function (Obstacle $why) use ($stack): TheLineChangeTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheLineChangeReads()->met($why);
            },
        );
    }
}
