<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\Opening;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\Diagnostics;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Launch;
use Modules\Kernel\Api\Obstacle;
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
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\Presenters\HowAStacksAgeReads;
use Modules\Operator\Internal\Presenters\HowTheLaunchReads;
use Modules\Operator\Internal\ViewModels\HowAStackLastWas;
use Modules\Operator\Internal\ViewModels\WhatTheLaunchWas;
use Modules\Operator\Internal\WhatTheSharingDid;
use Modules\Operator\Internal\WhereAStackIs;
use Modules\Operator\Internal\WhereTheFirstRunIs;
use Modules\Operator\Internal\WhetherItIsHeld;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * The screen a launch lands on: the machines this device knows, or an
 * invitation to introduce one.
 *
 * **Two states, one screen, because `/` is one route.** A first run is the empty
 * one and a stack already paired is the other, and a screen that answered only
 * the first is what this was until pairing existed — it told an operator who
 * had just paired a stack that nothing was paired. That is the failure mode of a screen named
 * for one of its states: the name stops being a description and starts being a
 * claim, and nothing checks a claim in a class name.
 *
 * The obvious thing for the empty case is refused — an empty operator
 * surface with a button somewhere in it. On a launch with no stack configured
 * the app has to say that setup happens at the machine, and *and
 * why*, rather than omitting it silently. Somebody who installed a companion
 * app reasonably expects to set the thing up from their phone, and a screen
 * that simply offers nothing teaches them the app is broken.
 *
 * **A row is a name and nothing else.** A stack's address is
 * the one thing on it that must never reach a screen, and a list is exactly
 * where somebody would put it to tell two entries apart. A device already holds
 * what tells them apart: the name the operator chose, which is the only part of
 * a stack they picked.
 *
 * **It reads no stack.** A usable frame is owed without waiting for a
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
 * be a binder call that waits on a locked store. A launch
 * reaches a usable frame without waiting for a reading, and *usually instant*
 * is not the same claim.
 *
 * The arch rule caught that, which is the argument for it being a rule rather
 * than a judgement: the assumption was made in good faith and was wrong about
 * the case that matters — a cold start on a device that has just been powered
 * on.
 *
 * **It answers for the case where there is nothing, and steps aside otherwise.**
 * The first-run case is a launch with no stack configured; a launch *with* one must
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
    protected ?Launch $launched = null;

    protected string $sharingWent = '';

    /** What to do about it, beside {@see sharingWent()}. */
    protected string $sharingRemedy = '';

    /**
     * How far into the first run the operator has read.
     *
     * Held on the screen rather than stored, which is the rule exactly: this is
     * what the operator did on a screen, and it is worth nothing once they have
     * a stack. Storing it would mean a device that remembers being told what
     * this app is — and something has to decide when to forget that, which is a
     * question already answered by tying the sequence to an empty
     * store.
     */
    protected WhereTheFirstRunIs $firstRunAt = WhereTheFirstRunIs::WhatThisIs;

    public function __construct(
        private readonly Stacks $stacks,
        private readonly SecureStorage $storage,
        private readonly Sharing $sharing,
        private readonly Verdicts $verdicts,
        private readonly Clock $clock,
        private readonly Opening $opening,
    ) {}

    /**
     * Ask the device again, because the operator said they were ready.
     *
     * Forgetting what was held rather than re-asking and comparing, which is
     * {@see HowThisStackIs::again()}'s shape: the next read rebuilds it, so
     * there is one path to an answer and it is the one every frame takes.
     *
     * There is a button for this rather than an automatic retry because something
     * refuses to ask again for something that was declined: an operator who
     * dismissed the prompt meant it, and a screen that immediately asked again
     * is the behaviour that teaches people to turn a feature off.
     */
    public function tryToUnlock(): void
    {
        $this->launched = null;
    }

    /**
     * Whether this device has been introduced to anything.
     *
     * Read here rather than held as state, because a screen returned to after
     * a pairing would otherwise still be answering with what it knew when it
     * was built. A screen keeps what the *operator* did; what the
     * device is configured for is not that.
     */
    public function nothingIsPairedYet(): bool
    {
        return $this->configured()->isEmpty();
    }

    /**
     * Which step of the first run this frame is drawing.
     *
     * The sequence is tied to an empty store rather than to a flag: it is
     * drawn inside the arm for *no stacks*, so a device that holds a pairing
     * cannot reach it and nothing has to remember that it was finished. A
     * boolean would have been a second answer to a question the store already
     * answers, and two answers is where they disagree.
     */
    public function firstRunIsAt(): WhereTheFirstRunIs
    {
        return $this->firstRunAt;
    }

    /** Read that step; show the next one. */
    public function goOn(): void
    {
        $this->firstRunAt = $this->firstRunAt->andThen();
    }

    /**
     * Leave the sequence, landing on pairing.
     *
     * Leaving has to land on pairing rather than on
     * nothing, and this is why the exit is a step rather than a route: an
     * operator who skipped arrives where the sequence was going, on the screen
     * they were already on, with the same two controls the last step offers.
     */
    public function skipAhead(): void
    {
        $this->firstRunAt = WhereTheFirstRunIs::Pairing;
    }

    /**
     * Whether this frame is a step of the first run rather than the screen.
     *
     * The one question the sequence asks of everything else on here, so that
     * *not during the sequence* has one spelling. What is asked for is a sequence
     * rather than a screen, and a sequence is only a sequence if the frame
     * carries the step and not the screen the step is on the way to.
     *
     * The pairing step is not it. That step *is* the screen's own controls
     * with a sentence above them — which is what is meant by leaving
     * landing on pairing rather than on nothing.
     */
    public function theFirstRunIsStillRunning(): bool
    {
        return $this->nothingIsPairedYet() && ! $this->firstRunAt->isThePairing();
    }

    /**
     * Whether the two pairing roads are drawn on this frame.
     *
     * Always, once anything is paired — the frame is a list and adding a second
     * machine is what somebody is on this screen to do. On a first run, only at
     * the end of the sequence: a *pair now* button visible under step one is
     * the wall the sequence exists to refuse, because a control that skips the
     * reading makes the reading optional and an optional sentence is unread.
     */
    public function pairingIsOffered(): bool
    {
        return ! $this->theFirstRunIsStillRunning();
    }

    /**
     * The machines this device knows, in the order they were paired.
     *
     * Answers {@see Configured} rather than a list of names, which is what
     * keeps the per-stack guarantees with the value: it holds no current stack,
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
     * Asked per stack rather than once, because each one's
     * session separate: an operator signed into the loft and not the shed needs
     * to see exactly that, and a single answer for the list would be wrong for
     * whichever stack it was not about.
     *
     * Read on every frame rather than held. A screen returned to after signing
     * in would otherwise still be showing what it knew when it was built, which
     * is the same argument {@see nothingIsPairedYet()} makes and the same rule
     * — a screen keeps what the *operator* did, and what this
     * device holds for a stack is not that.
     *
     * **The session itself does not come out of here.** `Resumed::either()` is
     * answered with a boolean and the session is dropped, so the one thing this
     * screen learns is whether to say *signed in* or *sign in*. An address will
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
     * The app opens on the overall verdict. The ordering `N2` calls
     * its whole design is *is anything wrong*, then *what*, then *may I fix it
     * from here*, and until this existed the first screen answered none of
     * them: a list of machines and the names their owner gave them, with the
     * verdict two taps and a network round trip away behind whichever stack
     * they guessed at first.
     *
     * **Held, never asked.** This screen opens the app and opening the app is
     * not a reason to talk to four machines — a screen is not a
     * poller and `F4` says a frame is not where a socket is opened. So the word
     * comes out of the store, which makes every one of them a retained reading
     * and is exactly why a session permits it: it may open a screen, and it
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
            waiting: static fn(): HowAStackLastWas => new HowAStacksAgeReads()->notYetKnown(),
            holding: static fn(Reading $reading): HowAStackLastWas => $reading->either(
                // A live reading cannot arrive here: everything this port
                // answers came out of a store. Answered rather than refused
                // because the arm is the type's, not this screen's — and the
                // honest answer for a word read just now is the word with no
                // age, which is what `notYetKnown` renders as no verdict.
                live: static fn(): HowAStackLastWas => new HowAStacksAgeReads()->notYetKnown(),
                retained: static fn(object $overall, Instant $at): HowAStackLastWas
                    => $overall instanceof Overall
                        ? new HowAStacksAgeReads()->read($overall, $at, $now)
                        : new HowAStacksAgeReads()->notYetKnown(),
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
     * {@see StackId::stored()} rather than the name, which is the separation in the
     * address bar such as it is: a name is what the operator chose and two
     * machines may share one, while the identifier is what this device minted
     * and they cannot. It is not a secret and it is not shown — a URI is how
     * the navigation stack addresses a screen, not something on the glass.
     */
    public function signInAt(Stack $stack): string
    {
        return WhereAStackIs::of($stack->id())->signIn();
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
            ? WhereAStackIs::of($stack->id())->health()
            : $this->signInAt($stack);
    }

    /**
     * Where the camera road into pairing is.
     *
     * Read off {@see AScreenWithoutAStack} rather than spelled in the template, for the
     * reason every other route here is: the provider registers from the same
     * case, so a rename cannot leave this button pointing at nothing. On a
     * first run it is the only way out of this screen.
     *
     * The typed road is not handed out here. Both roads are owed, and neither is
     * ask for both on this screen: the scanning screen offers the keyboard
     * beside the camera, which is where somebody is when the question is real,
     * and two controls of equal weight side by side here is this screen asking
     * an operator to choose an input method before they have decided to pair.
     */
    public function scanningIsAt(): string
    {
        return AScreenWithoutAStack::PairByScanning->value;
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
     * A diagnostic report is assembled for the operator to send, and
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

    /**
     * The launch, folded once into the shape a template can read.
     *
     * Held rather than asked again, because asking twice would prompt twice:
     * the platform's unlock is a system dialog, and a frame that drew it once
     * per field would put several in front of somebody. What is wanted is the
     * device's own authentication on a cold start and {@see Opening} is where
     * that order is decided — locked is asked before anything reads retained
     * state or touches a network. This screen renders the answer.
     *
     * One accessor handing out the value rather than one per field, which is
     * {@see WhatThisStackRuns::answer()}'s shape and its argument: a method per
     * field is a method this class spends on saying nothing, and the next fact
     * the template needs then costs one it does not have.
     *
     * Every arm is named even though each reader takes one field. That is
     * `Launch`'s design working rather than four arms saying one thing: an
     * optional arm would be a default, and a default is where two of the four
     * quietly become the same answer — which is exactly what is refused. Saying
     * it four times is the cost of never being able to forget one.
     *
     * Producing the answer is only half of it, which is why the
     * template branches on `->met` before it draws anything else. A launch that
     * decided *no network* and then drew the machine names and a stale verdict
     * would leave somebody tapping a stack their phone cannot reach, and the
     * distinction the type refuses to collapse would be discarded by the one
     * surface that was supposed to show it. `->remedy` is stated beside it
     * rather than folded into it, because an obstacle owes both: what happened
     * is a fact about the world and what to do about it is advice, and for a
     * launch the advice is the whole value — the fact is that a phone has no
     * signal, which its owner can usually see.
     */
    public function howItOpened(): WhatTheLaunchWas
    {
        $this->launched ??= $this->opening->found();

        return $this->launched->either(
            locked: static fn(): WhatTheLaunchWas => new HowTheLaunchReads()->locked(),
            unpaired: static fn(): WhatTheLaunchWas => new HowTheLaunchReads()->unpaired(),
            blocked: static fn(Obstacle $why): WhatTheLaunchWas => new HowTheLaunchReads()->blockedBy($why),
            ready: static fn(StackId $stack): WhatTheLaunchWas => new HowTheLaunchReads()->readyFor($stack),
        );
    }
}
