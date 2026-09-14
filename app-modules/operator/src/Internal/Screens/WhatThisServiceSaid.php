<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function count;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\LookingFor;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Saying;
use Modules\Kernel\Api\Scrollback;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Operator\Internal\WhatOneLineSays;
use Modules\Operator\Internal\WhatTheServiceTurnedOutToSay;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What one of a stack's services has been saying.
 *
 * `N2-R10` in four clauses: a read that is **bounded**, that is **searchable**,
 * that **names the service**, and that **states the view is a window rather
 * than the whole**. Three of them are held by {@see Scrollback}, where a screen
 * cannot drop them; this is where they reach somebody.
 *
 * **The search runs over the window and says so.** The endpoint takes a service
 * and a tail and no search term, so there is nothing to push down — what is
 * searched is what came back. The screen says that out loud rather than letting
 * an operator assume otherwise, because the assumption is the dangerous one: a
 * search that finds nothing reads as *the service never said it*, and what it
 * means is *not in the last two hundred lines*.
 *
 * **Narrowing does not re-ask.** Typing filters what is already held, which is
 * `N1-R17` read strictly — a screen that asked again per keystroke would open a
 * connection per letter to a machine on a home network, and would also change
 * what is being searched underneath the person searching it.
 *
 * **It reads one service, named in the route.** Not a picker over every service
 * the stack runs: this screen is reached from a finding that is already about
 * one, and a picker would need a second port and would put the choice before
 * the thing an operator came here to read.
 *
 * `Concealed` for the reason every stack-facing screen here is — and with more
 * force than most. A log line is whatever a service chose to print, which is the
 * one surface in this app where a secret could appear without anybody having
 * decided to put it there, so `N4-R18` keeps it off the app switcher and
 * `N4-R13`'s report is assembled from what the operator chooses to send.
 */
#[Lazy]
#[Concealed]
final class WhatThisServiceSaid extends NativeComponent
{
    /**
     * What somebody has typed into the search box.
     *
     * `protected` rather than private, which is what `NativeComponent`'s
     * property syncing needs to reach — it assigns from the parent class, so a
     * private member of a subclass becomes a dynamic property and the screen
     * silently stops holding what it thinks it holds.
     */
    protected string $looking = '';

    /** What came back, once the frame has asked. */
    protected ?Scrollback $held = null;

    public function __construct(
        private readonly Saying $saying,
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

    /**
     * The service this screen is about (`N2-R10`).
     *
     * Read from the route for the same reason the stack is. A screen holding
     * the service it was opened with, on a frame whose URI names another, would
     * show one service's lines under another's heading — which is the failure
     * an operator acts on, because they would go and restart the wrong thing.
     */
    public function service(): ServiceId
    {
        $named = $this->param('service');

        return ServiceId::called(is_string($named) ? $named : '');
    }

    /** Whether this device still holds a session for the stack (`N1-R44`). */
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

    /** What somebody has typed, for the box to hold it. */
    public function looking(): string
    {
        return $this->looking;
    }

    /**
     * The lines to show, oldest first.
     *
     * Narrowed by whatever is in the box, which is `N2-R10`'s *searchable* —
     * over the window rather than over the scrollback, because the window is
     * all there is.
     *
     * @return list<WhatOneLineSays>
     */
    public function lines(): array
    {
        return $this->answer()->lines;
    }

    /** How many are shown, which is fewer than arrived while a search is on. */
    public function howMany(): int
    {
        return count($this->answer()->lines);
    }

    /** How many came back, before anything narrowed them. */
    public function howManyArrived(): int
    {
        return $this->answer()->arrived;
    }

    /** How many were asked for, which is the bound this read was given (`N2-R10`). */
    public function bound(): int
    {
        return $this->answer()->bound;
    }

    /**
     * Whether the view stops where it was told to stop (`N2-R10`).
     *
     * The sentence the screen owes an operator. True and it says there may be
     * more behind this; false and it says this is all the stack kept — which is
     * a smaller claim than *all the service ever said*, and the catalogue line
     * is worded to keep the difference.
     */
    public function isAWindow(): bool
    {
        return $this->answer()->isAWindow;
    }

    /** Whether a search is narrowing what is shown. */
    public function isSearching(): bool
    {
        return $this->answer()->isSearching;
    }

    /**
     * Read the tail again (`N1-R3`).
     *
     * The action an obstacle must not take away. `N1-R3` says a control is not
     * hidden because the stack is unreachable — the app offers it and reports
     * the failure — and an obstacle screen with nothing on it does exactly what
     * the rule forbids: the only way back is leaving and returning, which
     * `N1-R27` names separately as the thing a screen must not rely on.
     *
     * It forgets the window as well as the fold, which the other screens have
     * no equivalent of. A held window is what lets this one search without
     * re-asking; keeping it through an *ask again* would hand back the same
     * lines and leave an operator tapping a button that changes nothing.
     */
    public function again(): void
    {
        $this->held = null;
    }

    /**
     * Where this machine's screens are.
     *
     * One accessor rather than one per destination, and {@see WhereAStackIs} is
     * the only place that knows a stack's routes.
     */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::what-this-service-said');
    }

    /**
     * What came back, asked once per frame and narrowed on every read.
     *
     * The window is held and the narrowing is not, which is the whole of how
     * this screen searches without re-asking: typing changes what is shown and
     * never what was fetched, so the claim about the edge of the view survives
     * the search — and a machine on a home network is spoken to once.
     */
    private function answer(): WhatTheServiceTurnedOutToSay
    {
        $held = $this->held;

        if ($held instanceof Scrollback) {
            return WhatTheServiceTurnedOutToSay::this($held, $this->lookingFor());
        }

        return $this->ask();
    }

    /** What somebody typed, as the value that knows whether it is a search. */
    private function lookingFor(): LookingFor
    {
        return LookingFor::text($this->looking);
    }

    /**
     * Resume the session, read the tail, and flatten what came back.
     *
     * Split from {@see answer()} because the two are different questions — when
     * to ask, and what asking produced — and because `H8` counts the doors
     * either would otherwise have.
     */
    private function ask(): WhatTheServiceTurnedOutToSay
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheServiceTurnedOutToSay => $this->read($stack, $session),
            notHeld: static fn(): WhatTheServiceTurnedOutToSay => WhatTheServiceTurnedOutToSay::signedOut(),
        );
    }

    /** What the service said, or what the operator met instead. */
    private function read(Stack $stack, Session $session): WhatTheServiceTurnedOutToSay
    {
        $looking = $this->lookingFor();

        return $this->saying->saidBy(
            $stack,
            $session,
            $this->service(),
            HowManyLines::asMuchAsAPhoneShows(),
        )->either(
            this_: function (Scrollback $scrollback) use ($looking): WhatTheServiceTurnedOutToSay {
                $this->held = $scrollback;

                return WhatTheServiceTurnedOutToSay::this($scrollback, $looking);
            },
            met: function (Obstacle $why) use ($stack): WhatTheServiceTurnedOutToSay {
                $this->letGoOfTheSession($why, $stack);

                return WhatTheServiceTurnedOutToSay::met($why);
            },
        );
    }

    /**
     * Stop holding a session the stack has just refused (`N3-R13`).
     *
     * The half of the requirement that is an effect rather than a value. The
     * fold already renders a refused credential as a signed-out app, so the
     * window this screen fetched never reaches the template — but a fold cannot
     * forget anything, and a session left in the store is resumed on the next
     * frame and refused again. The operator would be looking at a sign-in
     * prompt over a device that still believes it is signed in.
     *
     * Whether an obstacle means that is {@see Obstacle::meansWeAreSignedOut()}'s
     * decision, asked here rather than answered again, so this screen cannot
     * come to disagree with the fold it hands the same obstacle to.
     */
    private function letGoOfTheSession(Obstacle $why, Stack $stack): void
    {
        if (! $why->meansWeAreSignedOut()) {
            return;
        }

        $this->storage->forget($stack->id());
    }
}
