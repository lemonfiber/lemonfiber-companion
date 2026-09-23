<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function count;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Hosting;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhatRunsUnattended;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowHostingReads;
use Modules\Operator\Internal\ViewModels\WhatKeepsRunningTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What this machine keeps running when nobody is signed in.
 *
 * The question about the hours nobody was looking. Everything else this app
 * shows about a stack is true while somebody has it open; this is the one an
 * operator asks the morning after a reboot, and the one they cannot answer from
 * a phone any other way.
 *
 * **It asks once, when the frame is built, and holds what came back**, which is
 * {@see WhatStoppedComingIn}'s shape and what is required: one read per frame,
 * and a home network with a machine that may be asleep is the wrong thing to
 * talk to four times a second.
 *
 * **A machine that configures nothing is not a machine with nothing running.**
 * The two draw the same empty list, and only one of them invites an operator to
 * go looking for a switch. So the sentence saying what to do instead is a field
 * the template branches on rather than a row it might or might not have, and
 * the reading refuses a machine that claims the first and carries no sentence.
 *
 * **Nothing here is a button.** What is configured is the core's: this app does
 * not install a launch agent, does not take one back, and does not turn coming
 * back after a restart on or off. A phone that could would be a second place
 * the answer is decided, and the two would disagree the first time somebody
 * used the other one.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * runs is the household's business, and a diagnostic report is assembled from
 * what the operator chooses to send rather than from what a screen happened to
 * hold.
 */
#[Lazy]
#[Concealed]
final class WhatKeepsRunningHere extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * What came back, once the frame has asked.
     *
     * `protected` rather than private, which is what `NativeComponent`'s
     * property syncing needs to reach — it assigns from the parent class, so a
     * private member of a subclass becomes a dynamic property and the screen
     * silently stops holding what it thinks it holds.
     */
    protected ?WhatKeepsRunningTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Hosting $hosting,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, so there is one
     * answer to *which machine* and it is the one the URI names — the argument
     * {@see WhatStoppedComingIn::stack()} makes, and the same refusal for a
     * route naming a stack this device has forgotten.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /** How many are listed, which is what the empty state asks. */
    public function howMany(): int
    {
        return count($this->answer()->commands);
    }

    /**
     * Ask the machine again.
     *
     * The action an obstacle must not take away. A control is not hidden
     * because the stack is unreachable — the app offers it and reports the
     * failure — and an obstacle screen with nothing on it leaves no way back
     * but leaving and returning.
     */
    public function again(): void
    {
        $this->answered = null;
    }

    /**
     * Where this machine's screens are.
     *
     * One accessor rather than one per destination, and {@see WhereAStackIs} is
     * the only place that knows a stack's routes.
     */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::what-keeps-running-here');
    }

    /**
     * What came back, asked once per frame.
     *
     * One accessor handing out the value rather than one per field, which is
     * {@see WhatStoppedComingIn::answer()}'s shape and its argument: a method
     * per field is a method this class spends on saying nothing.
     */
    public function answer(): WhatKeepsRunningTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /**
     * Resume the session, ask the machine, and flatten what came back.
     *
     * Split from {@see answer()} because the two are different questions — when
     * to ask, and what asking produced — and because `H8` counts the doors
     * either would otherwise have.
     */
    private function ask(): WhatKeepsRunningTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatKeepsRunningTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatKeepsRunningTurnedOutToBe => new HowHostingReads()->signedOut(),
        );
    }

    /** What the machine said, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): WhatKeepsRunningTurnedOutToBe
    {
        return $this->hosting->keptRunningOn($stack, $session)->either(
            keeps: static fn(WhatRunsUnattended $running): WhatKeepsRunningTurnedOutToBe
                => new HowHostingReads()->this($running),
            met: function (Obstacle $why) use ($stack): WhatKeepsRunningTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowHostingReads()->met($why);
            },
        );
    }
}
