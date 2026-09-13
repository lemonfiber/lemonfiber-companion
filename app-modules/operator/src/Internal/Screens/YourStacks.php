<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\Diagnostics;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Sharing;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Verdicts;
use Modules\Kernel\Api\WhyNothingWasShared;
use Modules\Kernel\Api\WireVersion;
use Modules\Operator\Internal\HowAStackLastWas;
use Modules\Operator\Internal\WhatTheSharingDid;
use Modules\Operator\Internal\WhetherItIsHeld;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function sprintf;
use function view;

/**
 * The screen a launch lands on: the machines this device knows, or an
 * invitation to introduce one.
 *
 * **Two states, one screen, because `/` is one route.** `N1-R35` is the empty
 * one and `N1-R36` is the other, and a screen that answered only the first is
 * what this was until pairing existed — it told an operator who had just paired
 * a stack that nothing was paired. That is the failure mode of a screen named
 * for one of its states: the name stops being a description and starts being a
 * claim, and nothing checks a claim in a class name.
 *
 * `N1-R35` refuses the obvious thing for the empty case — an empty operator
 * surface with a button somewhere in it. On a launch with no stack configured
 * the app has to say that setup happens at the machine, and `N1-R4` adds *and
 * why*, rather than omitting it silently. Somebody who installed a companion
 * app reasonably expects to set the thing up from their phone, and a screen
 * that simply offers nothing teaches them the app is broken.
 *
 * **`N1-R15` is why a row is a name and nothing else.** A stack's address is
 * the one thing on it that must never reach a screen, and a list is exactly
 * where somebody would put it to tell two entries apart. `N1-R11` already says
 * what tells them apart: the name the operator chose, which is the only part of
 * a stack they picked.
 *
 * **It reads no stack.** `N1-R36` asks for a usable frame without waiting for a
 * reading, and the cheapest way to keep that is to have nothing to wait for:
 * what this shows is retained configuration, which is on the device. Reaching
 * a stack belongs to the screen for one stack, which is a different screen and
 * needs a session this app does not have yet.
 *
 * **It carries `#[Lazy]`, and the reason it does is worth keeping.** This file
 * used to say the attribute was unnecessary because the screen reached no
 * port. It reaches one now, and the first instinct was that `F4` still did not
 * apply — `Stacks::configured()` reads this device's own retained state rather
 * than a machine over a network, so where is the wait? In the platform store.
 * A keychain read is a call across a process boundary, and on Android it can
 * be a binder call that waits on a locked store. `N1-R36` says a launch
 * reaches a usable frame without waiting for a reading, and *usually instant*
 * is not the same claim.
 *
 * The arch rule caught that, which is the argument for it being a rule rather
 * than a judgement: the assumption was made in good faith and was wrong about
 * the case that matters — a cold start on a device that has just been powered
 * on.
 *
 * **It answers for the case where there is nothing, and steps aside otherwise.**
 * `N1-R35` is about a launch with no stack configured; a launch *with* one must
 * not land here, and until something asked, every launch did. So this asks —
 * which is also why it has a constructor at all, and why {@see
 * \Bootstrap\Composition\NativePHP\ScreenRouter} exists: NativePHP builds a screen with
 * `new`, and a screen that reached the container itself is the service location
 * `A3` refuses.
 *
 * `Internal` rather than `Api` because nothing outside this module names it —
 * the surface's own provider declares it, and E2's promise is that anything in
 * here can be renamed without reading another module.
 */
#[Lazy]
final class YourStacks extends NativeComponent
{
    /** What became of the last attempt to hand a report over, as a key. */
    protected string $sharingWent = '';

    /** What to do about it, beside {@see sharingWent()}. */
    protected string $sharingRemedy = '';
    public function __construct(
        private readonly Stacks $stacks,
        private readonly SecureStorage $storage,
        private readonly Sharing $sharing,
        private readonly Verdicts $verdicts,
        private readonly Clock $clock,
    ) {}

    /**
     * Whether this device has been introduced to anything.
     *
     * Read here rather than held as state, because a screen returned to after
     * a pairing would otherwise still be answering with what it knew when it
     * was built. `N1-R38` keeps what the *operator* did on a screen; what the
     * device is configured for is not that.
     */
    public function nothingIsPairedYet(): bool
    {
        return $this->configured()->isEmpty();
    }

    /**
     * The machines this device knows, in the order they were paired.
     *
     * Answers {@see Configured} rather than a list of names, which is what
     * keeps `N1-R11`'s guarantees with the value: it holds no current stack,
     * refuses one it does not know, and collapses a repeated identifier to the
     * later entry. A list of strings would leave each of those with the
     * template.
     *
     * Asked once per frame, like {@see nothingIsPairedYet()} and for the same
     * reason. A screen returned to after a pairing that held its list would
     * show the one it was built with.
     */
    public function configured(): Configured
    {
        return $this->stacks->configured();
    }

    /**
     * Whether this device is already signed into that stack.
     *
     * Asked per stack rather than once, because `N1-R11` keeps each one's
     * session separate: an operator signed into the loft and not the shed needs
     * to see exactly that, and a single answer for the list would be wrong for
     * whichever stack it was not about.
     *
     * Read on every frame rather than held. A screen returned to after signing
     * in would otherwise still be showing what it knew when it was built, which
     * is the same argument {@see nothingIsPairedYet()} makes and the same rule
     * — `N1-R38` keeps what the *operator* did on a screen, and what this
     * device holds for a stack is not that.
     *
     * **The session itself does not come out of here.** `Resumed::either()` is
     * answered with a boolean and the session is dropped, so the one thing this
     * screen learns is whether to say *signed in* or *sign in*. `N1-R15` will
     * not let a session reach a screen, and the shortest way to keep that true
     * is for the screen never to hold one.
     */
    public function isSignedInto(Stack $stack): bool
    {
        return $this->storage->resume($stack->id())->either(
            held: static fn(): WhetherItIsHeld => WhetherItIsHeld::itIs(),
            notHeld: static fn(): WhetherItIsHeld => WhetherItIsHeld::itIsNot(),
        )->held;
    }

    /**
     * What this stack last came to, with when it was read.
     *
     * `N2-R1` — the app opens on the overall verdict. The ordering `N2` calls
     * its whole design is *is anything wrong*, then *what*, then *may I fix it
     * from here*, and until this existed the first screen answered none of
     * them: a list of machines and the names their owner gave them, with the
     * verdict two taps and a network round trip away behind whichever stack
     * they guessed at first.
     *
     * **Held, never asked.** This screen opens the app and opening the app is
     * not a reason to talk to four machines — `N1-R17` says a screen is not a
     * poller and `F4` says a frame is not where a socket is opened. So the word
     * comes out of the store, which makes every one of them a retained reading
     * and is exactly why `N1-R24` permits it: it may open a screen, and it
     * carries when it was read.
     *
     * The age cannot be dropped on the way here. `Reading::either()` hands the
     * verdict and the moment to the same arm, so a row showing a word without
     * an age would have to have been given the age and thrown it away.
     */
    public function lastKnownOf(Stack $stack): HowAStackLastWas
    {
        // Read once here rather than inside the fold, so every row on one frame
        // is aged against the same moment. Two rows a second apart in wall time
        // would otherwise be aged against two different *nows*, which is a
        // difference nobody can see and a test cannot pin.
        $now = $this->clock->now();

        return $this->verdicts->lastKnownOf($stack->id())->either(
            waiting: static fn(): HowAStackLastWas => HowAStackLastWas::notYetKnown(),
            holding: static fn(Reading $reading): HowAStackLastWas => $reading->either(
                // A live reading cannot arrive here: everything this port
                // answers came out of a store. Answered rather than refused
                // because the arm is the type's, not this screen's — and the
                // honest answer for a word read just now is the word with no
                // age, which is what `notYetKnown` renders as no verdict.
                live: static fn(): HowAStackLastWas => HowAStackLastWas::notYetKnown(),
                retained: static fn(object $overall, Instant $at): HowAStackLastWas
                    => $overall instanceof Overall
                        ? HowAStackLastWas::read($overall, $at, $now)
                        : HowAStackLastWas::notYetKnown(),
            ),
        );
    }

    /**
     * Where tapping a stack goes.
     *
     * Built here rather than in the template so the URI has one spelling — the
     * template renders it, this decides it, and the route declaration in the
     * module's provider is the only other place it is written.
     *
     * {@see StackId::stored()} rather than the name, which is `N1-R11` in the
     * address bar such as it is: a name is what the operator chose and two
     * machines may share one, while the identifier is what this device minted
     * and they cannot. It is not a secret and it is not shown — a URI is how
     * the navigation stack addresses a screen, not something on the glass.
     */
    public function signInAt(Stack $stack): string
    {
        return sprintf('/stacks/%s/sign-in', $stack->id()->stored());
    }

    /**
     * Where tapping a stack actually goes.
     *
     * Straight to the report where this device still holds a session, and to
     * the sign-in screen where it does not. That is the whole of what the list
     * is *for*: an operator who is signed in wants to see their machine, not to
     * be asked for a password they already gave.
     *
     * Decided here rather than in the template, so the two URIs have one
     * spelling each and the branch is somewhere a test can drive it.
     */
    public function tappingGoesTo(Stack $stack): string
    {
        return $this->isSignedInto($stack)
            ? sprintf('/stacks/%s', $stack->id()->stored())
            : $this->signInAt($stack);
    }

    /** What became of the last attempt to hand a report over. */
    public function sharingWent(): string
    {
        return $this->sharingWent;
    }

    /** What to do about it. */
    public function sharingRemedy(): string
    {
        return $this->sharingRemedy;
    }

    /**
     * Assemble what can be said about this app, and hand it to the operator.
     *
     * `N4-R13`: a diagnostic report is assembled for the operator to send, and
     * the app does not send it. {@see Diagnostics} holds nothing that could
     * transmit and {@see Sharing} takes nowhere to transmit to, so *send it
     * somewhere* has no spelling on either side of this call.
     *
     * On this screen because it is the one an operator reaches from anywhere
     * and the one that works when nothing else does — a stack that cannot be
     * reached is exactly when somebody needs to ask for help, and a control
     * behind a reachable stack would be missing precisely then.
     *
     * The identifiers rather than the stacks, which is
     * {@see Diagnostics::assemble()}'s signature refusing rather than this
     * screen remembering: a `Stack` carries an address, and where on somebody's
     * network a machine lives is not something a support thread needs.
     */
    public function share(): void
    {
        // Collected by hand rather than with `iterator_to_array`, for the
        // reason `WorstFirst` writes out: `Configured` always holds a list, so
        // its `preserve_keys` argument cannot be wrong here and either value
        // produces the same array. An argument that cannot change the answer is
        // a line no test can defend.
        $ids = [];

        foreach ($this->configured() as $stack) {
            $ids[] = $stack->id();
        }

        $handed = $this->sharing->hand(
            Diagnostics::assemble(Shape::current(), WireVersion::newest(), ...$ids),
        );

        $handed->either(
            over: function (): WhatTheSharingDid {
                $this->sharingWent = '';
                $this->sharingRemedy = '';

                return new WhatTheSharingDid();
            },
            refused: function (WhyNothingWasShared $why): WhatTheSharingDid {
                $this->sharingWent = $why->saidOnTheScreen();
                $this->sharingRemedy = $why->remedy();

                return new WhatTheSharingDid();
            },
        );
    }

    /**
     * The frame, by name.
     *
     * A `View` rather than an `Element`: the base class accepts either, and a
     * Blade file is the half of a screen `tests/Templates` can read. An element
     * tree assembled in PHP would be invisible to every rule in that suite —
     * `F3`'s vocabulary check, `F5`'s screen-reader check and `L1`'s refusal of
     * an English sentence all work over the text of a template.
     */
    public function render(): View
    {
        return view('operator::your-stacks');
    }
}
