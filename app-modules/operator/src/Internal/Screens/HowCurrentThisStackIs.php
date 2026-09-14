<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function count;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\KeepingCurrent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Upkeep;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\WhatTheUpkeepTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Where this machine stands on being up to date.
 *
 * `N2-R15`'s screen. It opens on the answer — current, an update waiting, or
 * not looked at recently — because that is the decision an operator holding a
 * phone is making. They are not comparing version strings; they are deciding
 * whether tonight is the night.
 *
 * **It does not compare versions, and that is the requirement.** Which of the
 * three states the stack is in is the stack's answer, read and shown. An app
 * that worked it out from two strings would be wrong about a withdrawn
 * release, about a patch series, and about a stack whose channel the operator
 * changed — and wrong silently, because nothing on either side would compare
 * its opinion to the stack's.
 *
 * **A withdrawn release is left out of what is offered and said about what is
 * running.** Those are opposite errands: `N2-R16` refuses to offer one, and a
 * stack that is *on* one is something an operator has to be told. Dropping it
 * from both would leave them reading a screen that says nothing is wrong.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * runs is the household's business, and `N4-R13`'s diagnostic report is
 * assembled from what the operator chooses to send rather than from what a
 * screen happened to hold.
 */
#[Lazy]
#[Concealed]
final class HowCurrentThisStackIs extends NativeComponent
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
    protected ?WhatTheUpkeepTurnedOutToBe $answered = null;

    public function __construct(
        private readonly KeepingCurrent $keeping,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, so there is one
     * answer to *which machine* and it is the one the URI names.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /** How many releases are offered, which is what the empty state asks. */
    public function howMany(): int
    {
        return count($this->answer()->waiting);
    }

    /**
     * Ask the stack again (`N1-R3`).
     *
     * The action an obstacle must not take away. Forgetting what came back
     * rather than re-reading here, so the next accessor asks — which keeps this
     * one act and keeps `N1-R17` true: one asking per frame, and a frame that
     * starts when somebody taps.
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
        return view('operator::how-current-this-stack-is');
    }

    /**
     * What came back, asked once per frame.
     *
     * One accessor handing out the value rather than one per field: a method
     * per field is a method this class spends on saying nothing, and the next
     * fact the template needs then costs one it does not have.
     */
    public function answer(): WhatTheUpkeepTurnedOutToBe
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
    private function ask(): WhatTheUpkeepTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheUpkeepTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatTheUpkeepTurnedOutToBe => WhatTheUpkeepTurnedOutToBe::signedOut(),
        );
    }

    /** What the stack said, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): WhatTheUpkeepTurnedOutToBe
    {
        return $this->keeping->standing($stack, $session)->either(
            stands: static fn(Upkeep $upkeep): WhatTheUpkeepTurnedOutToBe
                => WhatTheUpkeepTurnedOutToBe::standing($upkeep),
            met: function (Obstacle $why) use ($stack): WhatTheUpkeepTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return WhatTheUpkeepTurnedOutToBe::met($why);
            },
        );
    }
}
