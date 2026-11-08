<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\AskingHowItCameBack;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Hosting;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatRunsUnattended;
use Modules\Kernel\Api\WhatTheLastStartCameTo;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowHostingReads;
use Modules\Operator\Internal\Presenters\HowTheLastStartReads;
use Modules\Operator\Internal\ReadsAStackOnceAFrame;
use Modules\Operator\Internal\ViewModels\TheLastStartAsShown;
use Modules\Operator\Internal\ViewModels\WhatKeepsRunningTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * While nobody was looking: what the machine came back to after it last started.
 *
 * When the machine last started and what the check after it came to, never a
 * check that has not run since the last restart drawn as passing; and beside
 * it what keeps the stack running and how many did not come back, read from
 * the same reading "What keeps running here" draws, which keeps the controls.
 *
 * **Two readings, one a frame.** The last start is the screen's own reading;
 * what keeps it running is read on the next frame, which the frame asks for
 * at once.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhileNobodyWasLooking extends NativeComponent
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use ReadsAStackOnceAFrame;

    /** What the stack said of its last start, once the frame has asked. Public for {@see WhatKeepsRunningHere::$answered}'s reason. */
    public ?TheLastStartAsShown $answered = null;

    /** What keeps it running, once a frame has read it. Public for the same reason. */
    public ?WhatKeepsRunningTurnedOutToBe $running = null;

    public function __construct(
        private readonly AskingHowItCameBack $asking,
        private readonly Hosting $hosting,
        private readonly SecureStorage $storage,
        private readonly Clock $clock,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** Let go of both readings, so the next frames take them again. */
    public function again(): void
    {
        $this->answered = null;
        $this->running = null;
    }

    public function render(): View
    {
        $this->aFrameBegins();

        return view('operator::while-nobody-was-looking');
    }

    /** What the stack said of its last start, asked once. */
    public function answer(): TheLastStartAsShown
    {
        if (! $this->answered instanceof TheLastStartAsShown) {
            $this->readsItsStack();
            $stack = $this->stack();
            $this->answered = $this->storage->resume($stack->id())->either(
                held: fn(Session $session): TheLastStartAsShown => $this->asked($stack, $session),
                notHeld: static fn(): TheLastStartAsShown => new HowTheLastStartReads()->signedOut(),
            );
        }

        return $this->answered;
    }

    /** Whether what keeps it running waits for the next frame, this one having read the last start. */
    public function runningIsBeingRead(): bool
    {
        return ! $this->running() instanceof WhatKeepsRunningTurnedOutToBe;
    }

    /** What keeps it running, read on a frame of its own, or nothing while it waits. */
    public function running(): ?WhatKeepsRunningTurnedOutToBe
    {
        if ($this->running instanceof WhatKeepsRunningTurnedOutToBe || ! $this->mayReadItsStack()) {
            return $this->running;
        }

        $stack = $this->stack();

        return $this->running = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatKeepsRunningTurnedOutToBe => $this->hosting->keptRunningOn($stack, $session)->either(
                keeps: static fn(WhatRunsUnattended $running): WhatKeepsRunningTurnedOutToBe => new HowHostingReads()->this($running),
                met: $this->lettingGoIfRefused($stack, new HowHostingReads()->met(...)),
            ),
            notHeld: static fn(): WhatKeepsRunningTurnedOutToBe => new HowHostingReads()->signedOut(),
        );
    }

    private function asked(Stack $stack, Session $session): TheLastStartAsShown
    {
        $now = $this->clock->now();

        return $this->asking->afterTheLastStart($stack, $session)->either(
            said: static fn(WhatTheLastStartCameTo $last): TheLastStartAsShown => new HowTheLastStartReads()->this($last, $now),
            met: $this->lettingGoIfRefused($stack, new HowTheLastStartReads()->met(...)),
        );
    }
}
