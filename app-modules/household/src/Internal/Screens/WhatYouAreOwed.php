<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Household\Internal\OffersTheAppsSettings;
use Modules\Household\Internal\Presenters\HowWhatAMemberAskedForReads;
use Modules\Household\Internal\Presenters\HowWhatAMemberIsOwedReads;
use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToBeOwed;
use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToHaveAsked;
use Modules\Household\Internal\ViewModels\WhatTheirRequestsTurnedOutToBe;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Owing;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatTheyAreOwed;
use Modules\Kernel\Api\WhatTheyAsked;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\TheWayAround;
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
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhatYouAreOwed extends NativeComponent
{
    use OffersTheAppsSettings;
    use FindsItsWayAroundTheHouse;
    use LetsGoOfARefusedSession;

    /**
     * What came back, once the frame has asked: both halves of one reading.
     *
     * `public`, which is what `NativeComponent`'s property syncing needs to
     * reach: since 4.5.1 it writes only public, non-static properties, and a
     * screen whose state it cannot write silently stops holding what it thinks
     * it holds. It is also what fills the view's data, so the compiled
     * template finds the variable rather than an undefined one.
     */
    public ?WhatTheirRequestsTurnedOutToBe $readings = null;

    public function __construct(
        private readonly Owing $owing,
        private readonly SecureStorage $storage,
        private readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
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
        return $this->around->stackOn($this);
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
        $this->readings = null;
    }

    /** What they are owed, from the one reading a frame takes. */
    public function answer(): WhatAMemberTurnedOutToBeOwed
    {
        return $this->readings()->owed;
    }

    /** What they have asked this machine for, from that same reading. */
    public function requests(): WhatAMemberTurnedOutToHaveAsked
    {
        return $this->readings()->asked;
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
     * Both halves of the reading, asked for once and held until asked again.
     *
     * One reading because they come back on one answer: asking once for what
     * they are owed and again for what they asked would read the same answer
     * twice in a frame.
     */
    private function readings(): WhatTheirRequestsTurnedOutToBe
    {
        return $this->readings ??= $this->ask();
    }

    /** Resume the session, ask the stack, and flatten both halves of what came back. */
    private function ask(): WhatTheirRequestsTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheirRequestsTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatTheirRequestsTurnedOutToBe => new WhatTheirRequestsTurnedOutToBe(
                new HowWhatAMemberIsOwedReads()->signedOut(),
                new HowWhatAMemberAskedForReads()->signedOut(),
            ),
        );
    }

    /** What the stack said of both halves. */
    private function asked(Stack $stack, Session $session): WhatTheirRequestsTurnedOutToBe
    {
        $read = $this->owing->theirRequests($stack, $session);

        return new WhatTheirRequestsTurnedOutToBe($this->owedFrom($read->owed(), $stack), $this->askedFrom($read->asked(), $stack));
    }

    /** What they are owed, or what the member met instead. */
    private function owedFrom(WhatTheyAreOwed $owed, Stack $stack): WhatAMemberTurnedOutToBeOwed
    {
        return $owed->either(
            told: static fn(Sentences $said): WhatAMemberTurnedOutToBeOwed
                => new HowWhatAMemberIsOwedReads()->these($said),
            refused: function (Obstacle $why) use ($stack): WhatAMemberTurnedOutToBeOwed {
                // A credential refused on this read is the same signed-out
                // device as one refused on any other, and a fold cannot forget
                // anything, so the store is told here, where the obstacle is
                // still in hand.
                $this->letGoOfTheSession($why, $stack);

                return new HowWhatAMemberIsOwedReads()->met($why);
            },
        );
    }

    /** What they asked for, or what the member met instead. */
    private function askedFrom(WhatTheyAsked $asked, Stack $stack): WhatAMemberTurnedOutToHaveAsked
    {
        return $asked->either(
            told: static fn(Requested $wanted): WhatAMemberTurnedOutToHaveAsked
                => new HowWhatAMemberAskedForReads()->these($wanted),
            refused: function (Obstacle $why) use ($stack): WhatAMemberTurnedOutToHaveAsked {
                // Told here as well as on the half beside it: whichever half met
                // the refusal is the one holding the obstacle when the store has
                // to hear about it. Letting go twice is letting go.
                $this->letGoOfTheSession($why, $stack);

                return new HowWhatAMemberAskedForReads()->met($why);
            },
        );
    }
}
