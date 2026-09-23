<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Household\Internal\LetsGoOfARefusedSession;
use Modules\Household\Internal\Presenters\HowAShelfReads;
use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToBeAbleToWatch;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Shelf;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Watching;
use Modules\Kernel\Api\Whose;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What this machine says the person holding the session may watch.
 *
 * The member's own shelf, and the whole of what this screen does is render it.
 * Which libraries they reach, what their age limit allows and what they are
 * entitled to were decided by the core before the list arrived, so nothing
 * here filters a row, sorts one or hides one.
 *
 * **There is no asking for anything on this screen.** Requesting something,
 * approving it and spending an allowance are the household surface's and are
 * reached from it. Keeping them off here is what stops the shelf growing a
 * second way to ask, able to disagree with the first about what a member is
 * allowed.
 *
 * **Nothing here composes an address.** A holding arrives as a name and a kind
 * and is drawn as one. What it takes to actually play it is the core's to hand
 * over, and this screen does not have it — building one out of a stack's
 * address and a holding's id would be this app holding a second copy of how
 * the library works, and would be wrong the first time the route to it is not
 * the one it assumed.
 *
 * `Concealed` for the reason every stack-facing screen is: what a household
 * holds is the household's business, and a diagnostic report is assembled from
 * what an operator chooses to send rather than from what a screen happened to
 * hold.
 */
#[Lazy]
#[Concealed]
final class WhatYouCanWatch extends NativeComponent
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
    public ?WhatAMemberTurnedOutToBeAbleToWatch $answered = null;

    public function __construct(
        private readonly Watching $watching,
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

    /**
     * Ask the machine again.
     *
     * The action an obstacle must not take away, and it earns its place twice
     * on this screen: a library out of reach is the case most likely to have
     * fixed itself by the time somebody reads the sentence saying so.
     */
    public function again(): void
    {
        $this->answered = null;
    }

    /** What came back, asked once per frame. */
    public function answer(): WhatAMemberTurnedOutToBeAbleToWatch
    {
        return $this->answered ??= $this->ask();
    }

    /** The way back to the machine this reading is about. */
    public function health(): string
    {
        return AStacksScreen::Health->forTheStack($this->stack()->id());
    }

    /** Where a session that has ended is renewed. */
    public function signIn(): string
    {
        return AStacksScreen::SignIn->forTheStack($this->stack()->id());
    }

    public function render(): View
    {
        return view('household::what-you-can-watch');
    }

    /**
     * Resume the session, ask the stack, and flatten what came back.
     *
     * Resumed once and folded twice — for the session and for whose it is —
     * rather than read twice, so the two cannot come from different frames.
     */
    private function ask(): WhatAMemberTurnedOutToBeAbleToWatch
    {
        $stack = $this->stack();
        $resumed = $this->storage->resume($stack->id());

        return $resumed->either(
            held: fn(Session $session): WhatAMemberTurnedOutToBeAbleToWatch => $resumed->whoseItIs(
                nobody: static fn(): WhatAMemberTurnedOutToBeAbleToWatch
                    => new HowAShelfReads()->signedOut(),
                theirs: fn(Whose $whose): WhatAMemberTurnedOutToBeAbleToWatch
                    => $this->asked($stack, $session, $whose),
            ),
            notHeld: static fn(): WhatAMemberTurnedOutToBeAbleToWatch
                => new HowAShelfReads()->signedOut(),
        );
    }

    /** What the stack said, or what the member met instead. */
    private function asked(Stack $stack, Session $session, Whose $whose): WhatAMemberTurnedOutToBeAbleToWatch
    {
        return $this->watching->theShelfOf($stack, $session, $whose)->either(
            told: static fn(Shelf $shelf): WhatAMemberTurnedOutToBeAbleToWatch
                => new HowAShelfReads()->these($shelf),
            outOfReach: static fn(Sentences $said): WhatAMemberTurnedOutToBeAbleToWatch
                => new HowAShelfReads()->outOfReach($said),
            refused: function (Obstacle $why) use ($stack): WhatAMemberTurnedOutToBeAbleToWatch {
                // A credential refused on this read is the same signed-out
                // device as one refused on any other, and a fold cannot forget
                // anything — so the store is told here, where the obstacle is
                // still in hand.
                $this->letGoOfTheSession($why, $stack);

                return new HowAShelfReads()->met($why);
            },
        );
    }
}
