<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Measuring;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhereTheRoomWent;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowTheRoomReads;
use Modules\Operator\Internal\ViewModels\TheRoomTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * How full this machine is, where the room went, and what is on its disk to weigh.
 *
 * Where each volume stands, what is free now and what will be once what is on
 * its way has landed, where the room went by category, and each completed
 * download with where it stands and what removing it would cost. The question
 * somebody asks when a phone says the disk is filling.
 *
 * **It reads and removes nothing.** Nothing is selected and nothing is
 * proposed: which downloads to remove is the operator's decision, made at the
 * machine. It asks once, when the frame is built, and reads the clock once
 * per answer so a network share's age is counted from one moment.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class HowFullThisMachineIs extends NativeComponent
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
    public ?TheRoomTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Measuring $measuring,
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
        return view('operator::how-full-this-machine-is');
    }

    /** What came back, asked once per frame. */
    public function answer(): TheRoomTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): TheRoomTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheRoomTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): TheRoomTurnedOutToBe => new HowTheRoomReads()->signedOut(),
        );
    }

    /** How full the machine said it is, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): TheRoomTurnedOutToBe
    {
        return $this->measuring->measuredOn($stack, $session)->either(
            measured: fn(WhereTheRoomWent $room): TheRoomTurnedOutToBe
                => new HowTheRoomReads()->this($room, $this->clock->now()),
            met: function (Obstacle $why) use ($stack): TheRoomTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheRoomReads()->met($why);
            },
        );
    }
}
