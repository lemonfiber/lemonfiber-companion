<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Rationing;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowTheLineReads;
use Modules\Operator\Internal\ViewModels\HowTheLineTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

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
final class HowTheLineIsSharedHere extends NativeComponent
{
    use LetsGoOfARefusedSession;

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
        private readonly Stacks $stacks,
        private readonly Clock $clock,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, for
     * {@see WhatStoppedComingIn::stack()}'s reason: there is one answer to
     * *which machine*, and it is the one the URI names.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /**
     * Ask the machine again.
     *
     * The action an obstacle must not take away, and one an operator who has
     * just changed something at the machine wants on a screen that answered.
     */
    public function again(): void
    {
        $this->answered = null;
    }

    /** Where this machine's screens are. */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::how-the-line-is-shared-here');
    }

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
            met: function (Obstacle $why) use ($stack): HowTheLineTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheLineReads()->met($why);
            },
        );
    }
}
