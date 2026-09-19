<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Household\Internal\LetsGoOfARefusedSession;
use Modules\Household\Internal\Presenters\HowWhatAMemberAskedForReads;
use Modules\Household\Internal\Presenters\HowWhatAMemberIsOwedReads;
use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToBeOwed;
use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToHaveAsked;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Owing;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What this machine says the person holding the session is owed.
 *
 * The member's own reading, and the first screen of this surface. What it draws
 * is what the core wrote to them: whether what they ask for needs approval,
 * what their period has left and when it makes room again, what is still
 * waiting and what was refused and why.
 *
 * **It renders sentences and composes none.** All of that is on the wire in
 * parts as well — a policy, a standing, two counts, an instant — and a screen
 * assembling its own wording out of them would be deciding what *within a
 * limit* means to a person, which is a permission model with a template around
 * it. The core decides; this hands over what the core said.
 *
 * **An empty answer and a refused one are drawn differently.** Both arrive with
 * no sentences in them and they are opposite things to read: one says there is
 * nothing to tell you and the other says this was not yours to ask. A screen
 * that drew a list would show the same blank frame for each, so the view model
 * is asked which it is rather than the template counting rows.
 *
 * **Nothing here is the operator's.** No report, no finding, no fault, no
 * lifecycle verb, no log, no other member — which is what a member surface is
 * refused, and what lets this screen exist inside this module at all. The one thing
 * it holds that an operator's screen also holds is the stack, because a reading
 * belongs to a machine.
 *
 * **It asks once, when the frame is built, and holds what came back**, which is
 * every stack-facing screen's shape here: one read per frame, because a home
 * network with a machine that may be asleep is the wrong thing to talk to four
 * times a second.
 *
 * `Concealed` for the reason every stack-facing screen is: what a house is owed
 * is the household's business, and a diagnostic report is assembled from what
 * an operator chooses to send rather than from what a screen happened to hold.
 */
#[Lazy]
#[Concealed]
final class WhatYouAreOwed extends NativeComponent
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
    protected ?WhatAMemberTurnedOutToBeOwed $answered = null;

    /**
     * What they have asked for, once the frame has asked.
     *
     * Held beside {@see $answered} rather than folded into it, because they are
     * two answers to two questions: a stack that said what somebody is owed and
     * could not say what they asked for has answered half, and one field for both
     * would have to throw one away to report the other.
     *
     * `protected` for {@see $answered}'s reason.
     */
    protected ?WhatAMemberTurnedOutToHaveAsked $listed = null;

    public function __construct(
        private readonly Owing $owing,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, so there is one
     * answer to *which machine* and it is the one the URI names — and a route
     * naming a stack this device has forgotten is refused rather than drawn.
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
     * The action an obstacle must not take away. A control is not hidden
     * because the stack is unreachable — the app offers it and reports the
     * failure — and an obstacle screen with nothing on it leaves the only way
     * back as leaving and returning.
     *
     * Forgetting what came back rather than re-reading here, so the next
     * accessor asks and one frame stays one asking.
     */
    public function again(): void
    {
        $this->answered = null;
        $this->listed = null;
    }

    /** What came back, asked once per frame. */
    public function answer(): WhatAMemberTurnedOutToBeOwed
    {
        return $this->answered ??= $this->ask();
    }

    /** What they have asked this machine for, asked once per frame. */
    public function requests(): WhatAMemberTurnedOutToHaveAsked
    {
        return $this->listed ??= $this->list();
    }

    /** The way back to the machine this reading is about. */
    public function health(): string
    {
        return AStacksScreen::Health->forTheStack($this->stack()->id());
    }

    /**
     * The member's other screen: what is already on their shelf.
     *
     * Beside this one rather than under it. What a member may ask for and
     * what they already have are two readings of two endpoints, and a person
     * who has just been told something arrived is a person about to look for
     * it — so the way across is here.
     */
    public function shelf(): string
    {
        return AStacksScreen::Shelf->forTheStack($this->stack()->id());
    }

    /** Where a session that has ended is renewed. */
    public function signIn(): string
    {
        return AStacksScreen::SignIn->forTheStack($this->stack()->id());
    }

    public function render(): View
    {
        return view('household::what-you-are-owed');
    }

    /**
     * Resume the session, ask the stack, and flatten what came back.
     *
     * Split from {@see answer()} because the two are different questions — when
     * to ask, and what asking produced.
     */
    private function ask(): WhatAMemberTurnedOutToBeOwed
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatAMemberTurnedOutToBeOwed => $this->asked($stack, $session),
            notHeld: static fn(): WhatAMemberTurnedOutToBeOwed
                => new HowWhatAMemberIsOwedReads()->signedOut(),
        );
    }

    /**
     * Resume the session, ask what they asked for, and flatten what came back.
     *
     * Its own resume rather than one shared with {@see ask()}. A session is not
     * held on this screen between the two — holding one is the thing this surface
     * spends its shape avoiding — and the store is on this device, so asking it
     * twice in a frame costs a read rather than a round trip.
     */
    private function list(): WhatAMemberTurnedOutToHaveAsked
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatAMemberTurnedOutToHaveAsked => $this->listed($stack, $session),
            notHeld: static fn(): WhatAMemberTurnedOutToHaveAsked
                => new HowWhatAMemberAskedForReads()->signedOut(),
        );
    }

    /** What the stack said, or what the member met instead. */
    private function asked(Stack $stack, Session $session): WhatAMemberTurnedOutToBeOwed
    {
        return $this->owing->toHandOver($stack, $session)->either(
            told: static fn(Sentences $said): WhatAMemberTurnedOutToBeOwed
                => new HowWhatAMemberIsOwedReads()->these($said),
            refused: function (Obstacle $why) use ($stack): WhatAMemberTurnedOutToBeOwed {
                // A credential refused on this read is the same signed-out
                // device as one refused on any other, and a fold cannot forget
                // anything — so the store is told here, where the obstacle is
                // still in hand.
                $this->letGoOfTheSession($why, $stack);

                return new HowWhatAMemberIsOwedReads()->met($why);
            },
        );
    }

    /** What they asked for, or what the member met instead. */
    private function listed(Stack $stack, Session $session): WhatAMemberTurnedOutToHaveAsked
    {
        return $this->owing->whatTheyAsked($stack, $session)->either(
            told: static fn(Requested $wanted): WhatAMemberTurnedOutToHaveAsked
                => new HowWhatAMemberAskedForReads()->these($wanted),
            refused: function (Obstacle $why) use ($stack): WhatAMemberTurnedOutToHaveAsked {
                // Told here as well as on the reading beside it, and not because
                // either is unsure: a fold cannot forget anything, and whichever
                // of the two met the refusal is the one holding the obstacle when
                // the store has to hear about it. Letting go twice is letting go.
                $this->letGoOfTheSession($why, $stack);

                return new HowWhatAMemberAskedForReads()->met($why);
            },
        );
    }
}
