<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function count;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Stalled;
use Modules\Kernel\Api\Stalling;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowAStallReads;
use Modules\Operator\Internal\ViewModels\WhatStoppedTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What the house asked for and never got.
 *
 * Four things must each be reachable and this is the first:
 * stuck downloads. It is the screen that answers the question an operator is
 * asked in person — *I asked for that film on Tuesday and it never arrived* —
 * which {@see WhatTheHouseholdAsked} can only half answer, because a request
 * marked as being fetched and a request that has been being fetched for nine
 * days look the same on that list.
 *
 * **It asks once, when the frame is built, and holds what came back**, which is
 * {@see WhatTheHouseholdAsked}'s shape and what is required: one read per
 * frame, and a home network with a machine that may be asleep is the
 * wrong thing to talk to four times a second. Every accessor below reads what
 * one asking produced.
 *
 * **It says how much of the listing it is showing, always.** The stack sends
 * `incomplete` and a screen that rendered rows without it would claim to be
 * complete by accident — which is the one wrong answer this screen can give
 * that nobody would go and check, because an operator shown three stalled
 * titles and told that is all of them stops looking.
 *
 * **Nothing here is a button.** What to do about a stalled download is a
 * decision made in the service that has it, and this app does not offer to
 * reach into one — the verbs are about services by form and by name, which is a
 * different screen and a different confirmation. This one says what stopped and
 * where, which is what makes the next step findable.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * watches is the household's business, and a diagnostic report is
 * assembled from what the operator chooses to send rather than from what a
 * screen happened to hold.
 */
#[Lazy]
#[Concealed]
final class WhatStoppedComingIn extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * What came back, once the frame has asked.
     *
     * `public`, which is what `NativeComponent`'s property syncing needs to
     * reach: since 4.5.1 it writes only public, non-static properties, and a
     * screen whose state it cannot write silently stops holding what it thinks
     * it holds. It is also what fills the view's data, so the compiled
     * template finds the variable rather than an undefined one.
     */
    public ?WhatStoppedTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Stalling $stalling,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, so there is one
     * answer to *which machine* and it is the one the URI names — the argument
     * {@see HowThisStackIs::stack()} makes, and the same refusal for a route
     * naming a stack this device has forgotten.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /** How many are shown, which is what the empty state asks. */
    public function howMany(): int
    {
        return count($this->answer()->stalled);
    }

    /**
     * Ask the stack again.
     *
     * The action an obstacle must not take away. A control is not
     * hidden because the stack is unreachable — the app offers it and reports
     * the failure — and an obstacle screen with nothing on it does exactly what
     * the rule forbids: the only way back is leaving and returning, which
     * is named separately as the thing a screen must not rely on.
     *
     * Forgetting what came back rather than re-reading here, so the next
     * accessor asks. That keeps this one act and keeps the reading rule true: one
     * asking per frame, and a frame that starts when somebody taps.
     */
    public function again(): void
    {
        $this->answered = null;
    }

    /**
     * Where this machine's screens are.
     *
     * One accessor rather than one per destination, and {@see WhereAStackIs} is
     * the only place that knows a stack's routes — six classes were each
     * spelling `/stacks/%s/sign-in` for themselves, so a rename had to be found
     * in all six and the one that was missed would be a button leading nowhere.
     */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::what-stopped-coming-in');
    }

    /**
     * What came back, asked once per frame.
     *
     * One accessor handing out the value rather than one per field, which is
     * {@see WhatThisStackRuns::answer()}'s shape and its argument: a method per
     * field is a method this class spends on saying nothing, and the next fact
     * the template needs then costs one it does not have. The template reads
     * the fields off what one asking produced, which is also the only thing
     * that could be true of them together.
     */
    public function answer(): WhatStoppedTurnedOutToBe
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
    private function ask(): WhatStoppedTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatStoppedTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatStoppedTurnedOutToBe => new HowAStallReads()->signedOut(),
        );
    }

    /** What the stack said, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): WhatStoppedTurnedOutToBe
    {
        return $this->stalling->stoppedOn($stack, $session)->either(
            these: static fn(Stalled $stalled): WhatStoppedTurnedOutToBe
                => new HowAStallReads()->these($stalled),
            met: function (Obstacle $why) use ($stack): WhatStoppedTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowAStallReads()->met($why);
            },
        );
    }
}
