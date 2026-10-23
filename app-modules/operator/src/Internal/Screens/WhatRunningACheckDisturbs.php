<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\ACheckThatDisturbs;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Asking;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\RunningACheck;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\AgoAsShown;
use Modules\Operator\Internal\Presenters\HowACheckThatDisturbsReads;
use Modules\Operator\Internal\Presenters\HowTheRunReads;
use Modules\Operator\Internal\ViewModels\TheCheckTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheDownloadsMovingAsShown;
use Modules\Operator\Internal\ViewModels\TheRunTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function trim;

/**
 * Running a check the stack leaves unverified because it disturbs something: what it disturbs, said first, and the run on a separate yes.
 *
 * What it disturbs, for how long the stack bounds it or that it gives no
 * bound, and the downloads the stack last said are moving, which the run
 * interrupts, are all said before the yes; this app adds no duration of its
 * own. The check run is the one the stack's report offers, never the route's.
 * The run is followed to its end, and the finding is drawn again from the
 * report it answered with, with how long ago it came back. Nothing here is a
 * rehearsal: the check is the disturbance.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatRunningACheckDisturbs extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::what-running-a-check-disturbs';

    /** The finding as the stack last reported it, once the frame has asked. */
    public ?TheCheckTurnedOutToBe $answered = null;

    /** The check a yes was sent for, empty until one is. */
    public string $running = '';

    /** The handle of the run being followed, where there is one. */
    public ?string $following = null;

    /** When the run's report came back, in whole seconds since the epoch, or nought before one has. */
    public int $ranAt = 0;

    public ?TheRunTurnedOutToBe $going = null;

    public function __construct(
        private readonly Asking $asking,
        private readonly RunningACheck $runs,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    public function again(): void
    {
        $this->answered = null;
    }

    /**
     * Run the check, as the screen showed it.
     *
     * The check sent is built from what was drawn above the yes, so what is
     * run is what the operator read; nothing is run where nothing was offered.
     */
    public function run(): void
    {
        $shown = $this->answer();

        if ($shown->check === '' || $this->howItIsGoing()->isWorking) {
            return;
        }

        $check = $shown->seconds === null
            ? ACheckThatDisturbs::unbounded($shown->disturbs, $shown->only)
            : ACheckThatDisturbs::boundedTo($shown->disturbs, $shown->only, $shown->seconds);
        $this->running = $shown->check;
        $this->going = $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->runs->run($stack, $session, $check));
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

    /** The finding as the stack last reported it, asked once per frame. */
    public function answer(): TheCheckTurnedOutToBe
    {
        $this->howItIsGoing();
        $stack = $this->stack();
        $named = $this->named();

        return $this->answered ??= $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheCheckTurnedOutToBe => $this->asking->about($stack, $session)->either(
                said: static fn(Report $report): TheCheckTurnedOutToBe => new HowACheckThatDisturbsReads()->in($report, $named),
                met: function (Obstacle $why) use ($stack): TheCheckTurnedOutToBe {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowACheckThatDisturbsReads()->met($why);
                },
            ),
            notHeld: static fn(): TheCheckTurnedOutToBe => new HowACheckThatDisturbsReads()->signedOut(),
        );
    }

    public function howItIsGoing(): TheRunTurnedOutToBe
    {
        $following = $this->following;

        return $this->going ??= $following === null
            ? new HowTheRunReads()->notAsked()
            : $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->runs->whatBecameOf($stack, $session, Job::named($following)));
    }

    /** How long ago the run's report came back, or nothing before one has. */
    public function ranAgo(): AgoAsShown
    {
        if ($this->ranAt === 0) {
            return AgoAsShown::unsaid();
        }

        $ran = Instant::atEpochSeconds($this->ranAt);
        $now = $this->listening->clock->now();

        return AgoAsShown::from(HowLongAgo::since($ran, $now), $ran, $now);
    }

    /**
     * The downloads the stack said are moving in what this screen last heard, which a run interrupts.
     *
     * Only from a summary heard now: a kept one names none, because a
     * download moves on long before it is opened again.
     *
     * @return list<string>
     */
    public function downloadsMoving(): array
    {
        return $this->heardSoFar()->summary(
            none: static fn(): TheDownloadsMovingAsShown => TheDownloadsMovingAsShown::none(),
            current: static fn(TheHealthSummary $summary): TheDownloadsMovingAsShown => TheDownloadsMovingAsShown::in($summary),
            asOf: static fn(): TheDownloadsMovingAsShown => TheDownloadsMovingAsShown::none(),
        )->names;
    }

    /** The check the route names, which only finds a finding in what the stack reported. */
    private function named(): string
    {
        $named = $this->param('service');

        return is_string($named) ? trim($named) : '';
    }

    /** @param Closure(Stack, Session): WhatBecameOfTheWork<Report> $asking */
    private function put(Closure $asking): TheRunTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheRunTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: static fn(): TheRunTurnedOutToBe => new HowTheRunReads()->signedOut(),
        );
    }

    /**
     * @param WhatBecameOfTheWork<Report> $became
     */
    private function shown(WhatBecameOfTheWork $became, Stack $stack): TheRunTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheRunTurnedOutToBe {
                $this->following = $job->shown();

                return new HowTheRunReads()->running();
            },
            answered: function (Report $report): TheRunTurnedOutToBe {
                $this->following = null;
                $this->ranAt = $this->listening->clock->now()->epochSeconds();
                $this->answered = new HowACheckThatDisturbsReads()->in($report, $this->running);

                return new HowTheRunReads()->answered();
            },
            ended: function (): TheRunTurnedOutToBe {
                $this->following = null;

                return new HowTheRunReads()->ended();
            },
            refused: function (ARefusalInItsWords $why): TheRunTurnedOutToBe {
                $this->following = null;

                return new HowTheRunReads()->refused($why);
            },
            met: function (Obstacle $why) use ($stack): TheRunTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheRunReads()->met($why);
            },
        );
    }
}
