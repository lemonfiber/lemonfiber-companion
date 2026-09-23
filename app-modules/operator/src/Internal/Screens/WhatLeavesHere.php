<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Outgoing;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhatLeavesThisMachine;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowWhatLeavesReads;
use Modules\Operator\Internal\ViewModels\WhatLeavesTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Everything that leaves this machine.
 *
 * The question somebody asks when they want to know what their machine says to
 * the world while nobody is watching: every request lemonfiber makes on its
 * own account — why, what it carries, what switches it off and what that
 * costs — and, apart from them, what each of the stack's services reaches.
 *
 * **Two lists, never one.** What lemonfiber sends is this product's to answer
 * for and to switch off; what a service sends is that service's doing, and
 * merged into one list a reader could not tell which connections they can stop
 * from here.
 *
 * **It asks once, when the frame is built, and holds what came back**, which is
 * {@see WhereThisComesFrom}'s shape, and it switches nothing off: that is a
 * setting, changed where settings are changed.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class WhatLeavesHere extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * What came back, once the frame has asked.
     *
     * `public`, for the reason {@see WhereThisComesFrom::$answered} gives.
     */
    public ?WhatLeavesTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Outgoing $outgoing,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, for
     * {@see WhereThisComesFrom::stack()}'s reason.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /** Ask the machine again, which an obstacle must not take away. */
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
        return view('operator::what-leaves-here');
    }

    /** What came back, asked once per frame. */
    public function answer(): WhatLeavesTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): WhatLeavesTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatLeavesTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatLeavesTurnedOutToBe => new HowWhatLeavesReads()->signedOut(),
        );
    }

    /** What the machine said leaves it, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): WhatLeavesTurnedOutToBe
    {
        return $this->outgoing->leaving($stack, $session)->either(
            leaving: static fn(WhatLeavesThisMachine $leaving): WhatLeavesTurnedOutToBe
                => new HowWhatLeavesReads()->this($leaving),
            met: function (Obstacle $why) use ($stack): WhatLeavesTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowWhatLeavesReads()->met($why);
            },
        );
    }
}
