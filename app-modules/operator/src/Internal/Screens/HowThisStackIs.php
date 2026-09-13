<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Asking;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Operator\Internal\WhatTheStackTurnedOutToBe;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function sprintf;
use function view;

/**
 * What one stack is doing, which is what the whole app is for.
 *
 * Pairing and signing in are both means to this end. `N1-R2` says an operator
 * away from the machine can see whether their stack is doing what it should,
 * and until this screen existed the app could get in and had nothing to show.
 *
 * **It asks once, when the frame is built, and holds what came back.** `N1-R17`
 * says a screen is not a poller: a home network and a machine that may be
 * asleep are the wrong things to talk to four times a second, and `F4` says
 * a frame is not where a socket is opened. The answer is a value on this
 * screen, so every accessor below reads what one asking produced rather than
 * asking again.
 *
 * **It reads the session back rather than being handed one.** A screen given a
 * session is a screen that has to be navigated to with one, which is a session
 * in a route — and `N1-R8` keeps them out of URLs for the reason a proxy log
 * gives. The keychain already holds one per stack; this asks it, and an
 * operator whose session has ended meets the sign-in screen rather than an
 * error.
 *
 * **`#[Concealed]`, because a report names what is wrong with somebody's
 * machine.** `N4-R18` is written about credentials and pairing material, and a
 * diagnostic report is the other thing on this app's screens worth keeping out
 * of the task switcher: it says which services are down, which disk is full,
 * and whether the tunnel is leaking.
 */
#[Lazy]
#[Concealed]
final class HowThisStackIs extends NativeComponent
{
    /** What came back, once the frame has asked. */
    private ?WhatTheStackTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Asking $asking,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, so there is one
     * answer to *which machine* and it is the one the URI names — the same
     * argument {@see SignIntoAStack::stack()} makes, and the same refusal for a
     * route naming a stack this device has forgotten.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /** Whether this device still holds a session for it (`N1-R44`). */
    public function isSignedIn(): bool
    {
        return $this->answer()->isSignedIn;
    }

    /** What the run amounts to, as a key, or the empty string where it did not run. */
    public function overall(): string
    {
        return $this->answer()->overall;
    }

    /** What the operator met instead, as a key, or the empty string where they did not. */
    public function met(): string
    {
        return $this->answer()->met;
    }

    /** What to do about it, beside {@see met()}. */
    public function remedy(): string
    {
        return $this->answer()->remedy;
    }

    /** Each finding, in the order the checks produced them. */
    public function findings(): Findings
    {
        return $this->answer()->findings;
    }

    /**
     * Where a session that has ended is answered.
     *
     * Built here rather than in the template so the URI has one spelling, the
     * same way {@see YourStacks::signInAt()} builds it for the list — and built
     * from the stack this screen is already about, so it cannot lead to another
     * machine's sign-in.
     */
    public function signInAt(): string
    {
        return sprintf('/stacks/%s/sign-in', $this->stack()->id()->stored());
    }

    /** The frame, by name. */
    public function render(): View
    {
        return view('operator::how-this-stack-is');
    }

    /**
     * What came back, asked once and held.
     *
     * The asking happens here rather than in a constructor because a
     * constructor runs before the route parameter is set — {@see Stack()} would
     * have nothing to read. Held rather than recomputed because every accessor
     * above calls this, and a screen that asked per accessor would open six
     * connections to render one frame.
     */
    private function answer(): WhatTheStackTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /**
     * Resume the session, ask the stack, and flatten what came back.
     *
     * Split from {@see answer()} because the two are different questions — when
     * to ask, and what asking produced — and because `H8` counts the doors
     * either would otherwise have.
     */
    private function ask(): WhatTheStackTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheStackTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatTheStackTurnedOutToBe => WhatTheStackTurnedOutToBe::signedOut(),
        );
    }

    /** What the stack said, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): WhatTheStackTurnedOutToBe
    {
        return $this->asking->about($stack, $session)->either(
            said: static fn(Report $report): WhatTheStackTurnedOutToBe => WhatTheStackTurnedOutToBe::said($report),
            met: static fn(Obstacle $why): WhatTheStackTurnedOutToBe => WhatTheStackTurnedOutToBe::met($why),
        );
    }
}
