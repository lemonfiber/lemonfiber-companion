<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Asking;
use Modules\Kernel\Api\Category;
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
use Modules\Kernel\Api\WhatARunCovers;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\AgoAsShown;
use Modules\Operator\Internal\Presenters\HowARunOfChecksReads;
use Modules\Operator\Internal\Presenters\HowTheRunReads;
use Modules\Operator\Internal\SaysWhatARunInterrupts;
use Modules\Operator\Internal\ViewModels\TheChecksTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheRunTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * Running every check, or every check of one family, the ones that disturb included: what the run disturbs, said first, and the run on a separate yes.
 *
 * Every finding the run covers that the stack left unverified because checking
 * it disturbs something is listed before the yes, each with what it disturbs
 * and the stack's bound or that it gave none, beside the downloads the stack
 * last said are moving, which the run interrupts; this app adds no duration of
 * its own. The run is followed to its end, and what it came back with is drawn
 * here, finding by finding, with how long ago: a reading taken afterwards would
 * leave every check that disturbs unverified again. Nothing here is a
 * rehearsal: the checks are the disturbance.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatRunningChecksDisturbs extends NativeComponent implements AwaitsAnOutcome
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;
    use SaysWhatARunInterrupts;

    public const string TEMPLATE = 'operator::what-running-checks-disturbs';

    /** What the run would disturb, once the frame has asked, and after a run what it came back with. */
    public ?TheChecksTurnedOutToBe $answered = null;

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

    /** Run what the route covers; nothing is sent while a run is still going, or for a route that names no run. */
    public function run(): void
    {
        $covers = $this->covers();

        if (! $covers instanceof WhatARunCovers || $this->howItIsGoing()->isWorking || ! $this->answer()->went->cameBack()) {
            return;
        }

        $this->going = $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->runs->runEvery($stack, $session, $covers));
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

    /** What the run would disturb, as the stack last reported it, asked once per frame. */
    public function answer(): TheChecksTurnedOutToBe
    {
        $this->howItIsGoing();
        $stack = $this->stack();
        $covers = $this->covers();

        return $this->answered ??= $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheChecksTurnedOutToBe => $this->asking->about($stack, $session)->either(
                said: static fn(Report $report): TheChecksTurnedOutToBe => $covers instanceof WhatARunCovers
                    ? new HowARunOfChecksReads()->before($report, $covers)
                    : new HowARunOfChecksReads()->unknown(),
                met: function (Obstacle $why) use ($stack): TheChecksTurnedOutToBe {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowARunOfChecksReads()->met($why);
                },
            ),
            notHeld: static fn(): TheChecksTurnedOutToBe => new HowARunOfChecksReads()->signedOut(),
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

    /** What the route's word covers: every check, a family's, or nothing this app knows. */
    private function covers(): ?WhatARunCovers
    {
        $word = $this->param('service');

        if ($word === WhatARunCovers::EVERY) {
            return WhatARunCovers::everyCheck();
        }

        $family = is_string($word) ? Category::tryFrom($word) : null;

        return $family instanceof Category ? WhatARunCovers::theFamily($family) : null;
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
                $this->answered = $this->answer()->withWhatTheRunFound(new HowARunOfChecksReads()->after($report, $this->covers() ?? WhatARunCovers::everyCheck()));

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
