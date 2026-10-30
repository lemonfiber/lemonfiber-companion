<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\ACheckThatDisturbs;
use Modules\Kernel\Api\Asking;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\RunningACheck;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\FollowsARunOfChecks;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowACheckThatDisturbsReads;
use Modules\Operator\Internal\SaysWhatARunInterrupts;
use Modules\Operator\Internal\ViewModels\ARunThatDisturbs;
use Modules\Operator\Internal\ViewModels\TheCheckTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

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
    use SaysWhatARunInterrupts;
    use FollowsARunOfChecks;

    public const string TEMPLATE = 'operator::what-running-a-check-disturbs';

    /** The finding as the stack last reported it, once the frame has asked. */
    public ?TheCheckTurnedOutToBe $answered = null;

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
        $shown = $this->answer()->found?->disturbs;

        if (! $shown instanceof ARunThatDisturbs || $this->howItIsGoing()->stands->isWorking()) {
            return;
        }

        $check = $shown->seconds === null
            ? ACheckThatDisturbs::unbounded($shown->disturbs, $shown->only)
            : ACheckThatDisturbs::boundedTo($shown->disturbs, $shown->only, $shown->seconds);
        $this->going = $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->runs->run($stack, $session, $check));
    }

    /** The finding as the stack last reported it, asked once per frame. */
    public function answer(): TheCheckTurnedOutToBe
    {
        $this->howItIsGoing();
        $stack = $this->stack();
        $named = $this->around->aboutOn($this);

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

    /** The finding drawn again from the report the run came back with. */
    protected function cameBackWith(Report $report): void
    {
        $this->answered = new HowACheckThatDisturbsReads()->in($report, $this->around->aboutOn($this));
    }
}
