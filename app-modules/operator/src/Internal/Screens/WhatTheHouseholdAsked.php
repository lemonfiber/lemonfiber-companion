<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function count;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Wanting;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowTheHouseholdsAskingReads;
use Modules\Operator\Internal\ViewModels\WhatTheHouseholdTurnedOutToWant;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What the people in the house have asked their stack for.
 *
 * `N2-R11` asks that requests awaiting a decision be visible from a phone, and
 * until this screen existed {@see Wanted}, {@see \Modules\Kernel\Api\Size} and
 * {@see \Modules\Kernel\Api\Waiting} were written, tested and reached by
 * nothing — the household was the one part of a stack an operator could not see
 * at all. It is also the part they are asked about in person: somebody in the
 * house asked for something last Tuesday and wants to know what happened.
 *
 * **It asks once, when the frame is built, and holds what came back**, which is
 * {@see HowThisStackIs}'s shape and `N1-R65`'s requirement: one read per frame,
 * and a home network with a machine that may be asleep is the wrong
 * thing to talk to four times a second. Every accessor below reads what one
 * asking produced.
 *
 * **It shows everything the house asked for, not only what is waiting.**
 * `N2-R11` is about the decisions, and those are marked — but a screen that
 * listed only them would answer *what must I decide* and leave *what became of
 * the thing I asked for* unanswered, which is the question the household
 * actually asks its operator. The two are told apart on the row rather than by
 * hiding one of them.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * watches is the household's business, and `N4-R13`'s diagnostic report is
 * assembled from what the operator chooses to send rather than from what a
 * screen happened to hold.
 */
#[Lazy]
#[Concealed]
final class WhatTheHouseholdAsked extends NativeComponent
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
    protected ?WhatTheHouseholdTurnedOutToWant $answered = null;

    public function __construct(
        private readonly Wanting $wanting,
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
        return count($this->answer()->requests);
    }

    /**
     * Ask the stack again (`N1-R3`).
     *
     * The action an obstacle must not take away. `N1-R3` says a control is not
     * hidden because the stack is unreachable — the app offers it and reports
     * the failure — and an obstacle screen with nothing on it does exactly what
     * the rule forbids: the only way back is leaving and returning, which
     * `N1-R27` names separately as the thing a screen must not rely on.
     *
     * Forgetting what came back rather than re-reading here, so the next
     * accessor asks. That keeps this one act and keeps `N1-R65` true: one
     * asking per frame, and a frame that starts when somebody taps.
     */
    public function again(): void
    {
        $this->answered = null;
    }

    /**
     * Where this machine's screens are.
     *
     * One accessor rather than one per destination, and {@see WhereAStackIs}
     * is the only place that knows a stack's routes — six classes were each
     * spelling `/stacks/%s/sign-in` for themselves, so a rename had to be found
     * in all six and the one that was missed would be a button leading nowhere.
     */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::what-the-household-asked');
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
    public function answer(): WhatTheHouseholdTurnedOutToWant
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
    private function ask(): WhatTheHouseholdTurnedOutToWant
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheHouseholdTurnedOutToWant => $this->asked($stack, $session),
            notHeld: static fn(): WhatTheHouseholdTurnedOutToWant
                => new HowTheHouseholdsAskingReads()->signedOut(),
        );
    }

    /** What the stack said, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): WhatTheHouseholdTurnedOutToWant
    {
        return $this->wanting->askedOf($stack, $session)->either(
            these: static fn(Requested $wanted): WhatTheHouseholdTurnedOutToWant
                => new HowTheHouseholdsAskingReads()->these($wanted),
            met: function (Obstacle $why) use ($stack): WhatTheHouseholdTurnedOutToWant {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheHouseholdsAskingReads()->met($why);
            },
        );
    }
}
