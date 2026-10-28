<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Asking;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
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
use Modules\Operator\Internal\FollowsARunOfChecks;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowARunOfChecksReads;
use Modules\Operator\Internal\SaysWhatARunInterrupts;
use Modules\Operator\Internal\ViewModels\TheChecksTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
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
    use FollowsARunOfChecks;

    public const string TEMPLATE = 'operator::what-running-checks-disturbs';

    /** What the run would disturb, once the frame has asked, and after a run what it came back with. */
    public ?TheChecksTurnedOutToBe $answered = null;

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

        if (! $covers instanceof WhatARunCovers || $this->howItIsGoing()->stands->isWorking() || ! $this->answer()->went->cameBack()) {
            return;
        }

        $this->going = $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->runs->runEvery($stack, $session, $covers));
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

    /** What the run came back with, drawn beside what it was asked to disturb. */
    protected function cameBackWith(Report $report): void
    {
        $this->answered = $this->answer()->withWhatTheRunFound(new HowARunOfChecksReads()->after($report, $this->covers() ?? WhatARunCovers::everyCheck()));
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
}
