<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Copying;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Storing;
use Modules\Kernel\Api\TheCopies;
use Modules\Kernel\Api\WhatThisMachineKeeps;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowWhatIsKeptReads;
use Modules\Operator\Internal\ViewModels\TheCopiesAsFound;
use Modules\Operator\Internal\ViewModels\WhatIsKeptTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What this machine keeps, where, and why, and the copies of the stack it holds.
 *
 * Each thing kept is shown with where it is and why the stack keeps it. Whether
 * it holds a secret is said, and the secret is not shown: nothing this screen
 * reads carries a value. What is on the machine and is not the stack's is
 * listed beside it.
 *
 * **Two readings.** What the stack keeps is asked first, and an obstacle there
 * is the screen's obstacle, as on every other screen. The copies are asked
 * only after that has answered. A list that could not be read is drawn as its
 * own sentence, never as an empty list.
 *
 * **It reads and changes nothing.** Taking a copy and putting one back are
 * done at the machine. It asks once, when the frame is built.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class WhatThisMachineKeepsHere extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * What came back, once the frame has asked.
     *
     * `public` for {@see HowTheLineIsSharedHere::$answered}'s reason.
     */
    public ?WhatIsKeptTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Storing $storing,
        private readonly Copying $copying,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, for
     * {@see WhatStoppedComingIn::stack()}'s reason.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /** Ask the machine again, both readings. */
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
        return view('operator::what-this-machine-keeps-here');
    }

    /** What came back, asked once per frame. */
    public function answer(): WhatIsKeptTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): WhatIsKeptTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatIsKeptTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatIsKeptTurnedOutToBe => new HowWhatIsKeptReads()->signedOut(),
        );
    }

    /** What the machine said it keeps, with its copies, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): WhatIsKeptTurnedOutToBe
    {
        return $this->storing->storedOn($stack, $session)->either(
            kept: fn(WhatThisMachineKeeps $keeps): WhatIsKeptTurnedOutToBe
                => new HowWhatIsKeptReads()->this($keeps, $this->copies($stack, $session)),
            met: function (Obstacle $why) use ($stack): WhatIsKeptTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowWhatIsKeptReads()->met($why);
            },
        );
    }

    /** The copies the machine holds, or what stopped them being listed. */
    private function copies(Stack $stack, Session $session): TheCopiesAsFound
    {
        return $this->copying->copiesOn($stack, $session)->either(
            copies: static fn(TheCopies $copies): TheCopiesAsFound => new HowWhatIsKeptReads()->copies($copies),
            met: function (Obstacle $why) use ($stack): TheCopiesAsFound {
                $this->letGoOfTheSession($why, $stack);

                return new HowWhatIsKeptReads()->copiesMet($why);
            },
        );
    }
}
