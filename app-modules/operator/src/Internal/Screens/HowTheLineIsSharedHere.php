<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Rationing;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\LooksAgainWhileItMoves;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheLineReads;
use Modules\Operator\Internal\ViewModels\HowTheLineTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksAgain;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * How this machine shares its line with the household.
 *
 * Where the line stands and what that means, each direction's limit, what the
 * line was measured to carry — declared or observed, through the tunnel or
 * beside it — and the monthly cap with which of pause, throttle or continue
 * reaching it brings. The question somebody asks when the house says the
 * internet is slow, or before a month with a cap on it.
 *
 * **It reads and changes nothing.** Limits and caps are set where the core is
 * configured. It asks once, when the frame is built, and reads the clock once
 * per answer so the measurement's age is counted from one moment — the shape
 * {@see WhatWasChangedHere} has.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class HowTheLineIsSharedHere extends NativeComponent
{
    use AsksTheStackAgain;
    use LooksAgainWhileItMoves;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;
    use AsksAgain;

    public const string TEMPLATE = 'operator::how-the-line-is-shared-here';

    /**
     * What came back, once the frame has asked.
     *
     * `public`, which is what `NativeComponent`'s property syncing needs to
     * reach: since 4.5.1 it writes only public, non-static properties, and a
     * screen whose state it cannot write silently stops holding what it thinks
     * it holds. The same reason {@see WhatStoppedComingIn::$answered} gives.
     */
    public ?HowTheLineTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Rationing $rationing,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        private readonly Clock $clock,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** What came back, asked once per frame. */
    public function answer(): HowTheLineTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): HowTheLineTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheLineTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): HowTheLineTurnedOutToBe => new HowTheLineReads()->signedOut(),
        );
    }

    /** How the machine said its line is shared, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): HowTheLineTurnedOutToBe
    {
        return $this->rationing->rationedOn($stack, $session)->either(
            shared: fn(HowTheLineIsShared $line): HowTheLineTurnedOutToBe
                => new HowTheLineReads()->this($line, $this->clock->now()),
            met: $this->lettingGoIfRefused($stack, new HowTheLineReads()->met(...)),
        );
    }
}
