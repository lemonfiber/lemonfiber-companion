<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\SelfChecking;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\ThisCopyOfLemonfiber;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowThisCopyReads;
use Modules\Operator\Internal\ViewModels\ThisCopyTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Which version of lemonfiber this machine runs, how it got there, and whether a newer one exists.
 *
 * How it was installed comes first, because it decides who can replace it.
 * Where there is a newer version, what it would bring and what updating
 * leaves behind are said, with the exact command to run at the machine, or
 * why there is none.
 *
 * **It reads and updates nothing.** Updating lemonfiber is done at the
 * machine, and the services are updated from their own screen. It asks once,
 * when the frame is built.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class WhatIsRunningHere extends NativeComponent
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
    public ?ThisCopyTurnedOutToBe $answered = null;

    public function __construct(
        private readonly SelfChecking $checking,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
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
        return view('operator::what-is-running-here');
    }

    /** What came back, asked once per frame. */
    public function answer(): ThisCopyTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): ThisCopyTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): ThisCopyTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): ThisCopyTurnedOutToBe => new HowThisCopyReads()->signedOut(),
        );
    }

    /** What the machine said of its running copy, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): ThisCopyTurnedOutToBe
    {
        return $this->checking->checkedOn($stack, $session)->either(
            found: static fn(ThisCopyOfLemonfiber $copy): ThisCopyTurnedOutToBe => new HowThisCopyReads()->this($copy),
            met: function (Obstacle $why) use ($stack): ThisCopyTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowThisCopyReads()->met($why);
            },
        );
    }
}
