<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\TheFrontDoor;
use Modules\Kernel\Api\Welcoming;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowTheFrontDoorReads;
use Modules\Operator\Internal\ViewModels\TheFrontDoorTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * The household's front door: where it stands, whether it was chosen or worked out, and what else they can reach.
 *
 * Every address on it is one the stack sent, with the stack's caution beside
 * it; none is put together here. Each service beside the door says what it
 * is to the household and why it is not the door.
 *
 * It names no door and asks once, when the frame is built.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class WhereTheHouseholdComesIn extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * What came back, once the frame has asked.
     *
     * `public`, for the reason {@see WhatIsRunningHere::$answered} gives.
     */
    public ?TheFrontDoorTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Welcoming $welcoming,
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
        return view('operator::where-the-household-comes-in');
    }

    /** What came back, asked once per frame. */
    public function answer(): TheFrontDoorTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): TheFrontDoorTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheFrontDoorTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): TheFrontDoorTurnedOutToBe => new HowTheFrontDoorReads()->signedOut(),
        );
    }

    /** What the machine said of its door, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): TheFrontDoorTurnedOutToBe
    {
        return $this->welcoming->frontDoorOf($stack, $session)->either(
            found: static fn(TheFrontDoor $door): TheFrontDoorTurnedOutToBe => new HowTheFrontDoorReads()->this($door),
            met: function (Obstacle $why) use ($stack): TheFrontDoorTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheFrontDoorReads()->met($why);
            },
        );
    }
}
