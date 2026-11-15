<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\AskingTheQueue;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowTheQueueHeals;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheHealingReads;
use Modules\Operator\Internal\ViewModels\TheHealingAsShown;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * How the queue heals itself: every item it would act on and why, before it does, and every run with each act on its own.
 *
 * It reads and never acts: no item, strike count or grace window is changed
 * from here.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class TheQueueHealingItself extends NativeComponent
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;

    /** How the queue heals itself, once the frame has asked. Public for {@see WhatKeepsRunningHere::$answered}'s reason. */
    public ?TheHealingAsShown $answered = null;

    public function __construct(
        private readonly AskingTheQueue $asking,
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
        return view('operator::the-queue-healing-itself');
    }

    /** How the queue heals itself, asked once. */
    public function answer(): TheHealingAsShown
    {
        if (! $this->answered instanceof TheHealingAsShown) {
            $stack = $this->stack();
            $this->answered = $this->storage->resume($stack->id())->either(
                held: fn(Session $session): TheHealingAsShown => $this->asked($stack, $session),
                notHeld: static fn(): TheHealingAsShown => new HowTheHealingReads()->signedOut(),
            );
        }

        return $this->answered;
    }

    private function asked(Stack $stack, Session $session): TheHealingAsShown
    {
        $now = $this->clock->now();

        return $this->asking->howItHeals($stack, $session)->either(
            said: static fn(HowTheQueueHeals $queue): TheHealingAsShown => new HowTheHealingReads()->this($queue, $now),
            met: $this->lettingGoIfRefused($stack, new HowTheHealingReads()->met(...)),
        );
    }
}
