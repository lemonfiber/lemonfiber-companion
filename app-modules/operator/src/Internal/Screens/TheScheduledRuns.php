<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\RunningOnASchedule;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheScheduledServices;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheScheduleReads;
use Modules\Operator\Internal\RunsOneNow;
use Modules\Operator\Internal\ViewModels\TheScheduleAsShown;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Every run-and-exit service the stack reports and how its last run went, and running one at once, to the operator alone.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class TheScheduledRuns extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use RunsOneNow;

    /** The run-and-exit services, once the frame has asked. */
    public ?TheScheduleAsShown $answered = null;

    public function __construct(
        private readonly RunningOnASchedule $scheduling,
        private readonly SecureStorage $storage,
        private readonly Clock $clock,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** Let go of the reading, so the next frame takes it again. */
    public function again(): void
    {
        $this->answered = null;
    }

    public function render(): View
    {
        return view('operator::the-scheduled-runs');
    }

    /** The run-and-exit services, asked once. */
    public function answer(): TheScheduleAsShown
    {
        if (! $this->answered instanceof TheScheduleAsShown) {
            $stack = $this->stack();
            $this->answered = $this->storage->resume($stack->id())->either(
                held: fn(Session $session): TheScheduleAsShown => $this->asked($stack, $session),
                notHeld: static fn(): TheScheduleAsShown => new HowTheScheduleReads()->signedOut(),
            );
        }

        return $this->answered;
    }

    private function asked(Stack $stack, Session $session): TheScheduleAsShown
    {
        $now = $this->clock->now();

        return $this->scheduling->scheduled($stack, $session)->either(
            said: static fn(TheScheduledServices $services): TheScheduleAsShown => new HowTheScheduleReads()->these($services, $now),
            met: $this->lettingGoIfRefused($stack, new HowTheScheduleReads()->met(...)),
        );
    }
}
