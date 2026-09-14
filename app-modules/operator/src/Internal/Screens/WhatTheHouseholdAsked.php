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
use Modules\Operator\Internal\WhatOneRequestSays;
use Modules\Operator\Internal\WhatTheHouseholdTurnedOutToWant;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function sprintf;
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
 * {@see HowThisStackIs}'s shape and `N1-R17`'s requirement: a screen is not a
 * poller, and a home network with a machine that may be asleep is the wrong
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

    /** Whether this device still holds a session for it (`N1-R44`). */
    public function isSignedIn(): bool
    {
        return $this->answer()->isSignedIn;
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

    /**
     * Every request the house has made, as rows a template can read.
     *
     * In the stack's own order and not reordered here. Putting what is waiting
     * first is tempting and wrong for this list: the order the stack lists them
     * in is the order they were asked for, which is how the person who asked
     * remembers theirs — and an operator scanning for *the thing my daughter
     * asked about on Tuesday* is looking for a position, not a priority.
     *
     * @return list<WhatOneRequestSays>
     */
    public function requests(): array
    {
        return $this->answer()->requests;
    }

    /** How many are shown, which is what the empty state asks. */
    public function howMany(): int
    {
        return count($this->answer()->requests);
    }

    /**
     * How many are waiting on the operator, which is what `N2-R11` is about.
     *
     * Counted by {@see Requested::waiting()} rather than
     * here, so the line between waiting and not is drawn once — by
     * {@see \Modules\Kernel\Api\Waiting::wantsADecision()} — and this screen
     * cannot come to disagree with another about what is waiting.
     */
    public function howManyWaiting(): int
    {
        return $this->answer()->waiting;
    }

    /** Where the screen about this machine's health is. */
    public function healthIsAt(): string
    {
        return sprintf('/stacks/%s', $this->stack()->id()->stored());
    }

    /** Where signing in again happens (`N1-R44`). */
    public function signInAt(): string
    {
        return sprintf('/stacks/%s/sign-in', $this->stack()->id()->stored());
    }

    public function render(): View
    {
        return view('operator::what-the-household-asked');
    }

    /** What came back, asked once per frame. */
    private function answer(): WhatTheHouseholdTurnedOutToWant
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
            notHeld: static fn(): WhatTheHouseholdTurnedOutToWant => WhatTheHouseholdTurnedOutToWant::signedOut(),
        );
    }

    /** What the stack said, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): WhatTheHouseholdTurnedOutToWant
    {
        return $this->wanting->askedOf($stack, $session)->either(
            these: static fn(Requested $wanted): WhatTheHouseholdTurnedOutToWant
                => WhatTheHouseholdTurnedOutToWant::these($wanted),
            met: static fn(Obstacle $why): WhatTheHouseholdTurnedOutToWant
                => WhatTheHouseholdTurnedOutToWant::met($why),
        );
    }
}
