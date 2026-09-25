<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Advising;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhatToWatchOn;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowTheAdviceReads;
use Modules\Operator\Internal\ViewModels\TheAdviceTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Which app somebody in the household should watch on, device by device, and what to do when it does not work.
 *
 * Each device carries its rating, and a poorly served one carries what to use
 * instead; *fallback* is drawn as the answer it is. That all of it works only
 * on the household network is said once, above the devices.
 *
 * It installs nothing and asks once, when the frame is built.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class WhichAppToWatchOn extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * What came back, once the frame has asked.
     *
     * `public`, for the reason {@see WhatIsRunningHere::$answered} gives.
     */
    public ?TheAdviceTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Advising $advising,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, for
     * {@see WhatIsRunningHere::stack()}'s reason.
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
        return view('operator::which-app-to-watch-on');
    }

    /** What came back, asked once per frame. */
    public function answer(): TheAdviceTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): TheAdviceTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheAdviceTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): TheAdviceTurnedOutToBe => new HowTheAdviceReads()->signedOut(),
        );
    }

    /** What the machine advised, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): TheAdviceTurnedOutToBe
    {
        return $this->advising->advisedBy($stack, $session)->either(
            found: static fn(WhatToWatchOn $advice): TheAdviceTurnedOutToBe => new HowTheAdviceReads()->this($advice),
            met: function (Obstacle $why) use ($stack): TheAdviceTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheAdviceReads()->met($why);
            },
        );
    }
}
