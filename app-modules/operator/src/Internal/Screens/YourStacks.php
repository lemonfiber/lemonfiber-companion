<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\ClearingWhatCannotBeRead;
use Modules\Connection\Api\Opening;
use Modules\Connection\Api\RemovingAStack;
use Modules\Connection\Api\WhatWasKeptAtOpening;
use Modules\Health\Api\KeepingTheLastReading;
use Modules\Kernel\Api\Capture;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\Diagnostics;
use Modules\Kernel\Api\Hearing;
use Modules\Kernel\Api\Launch;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Sharing;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Standings;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhyNothingWasShared;
use Modules\Kernel\Api\WireVersion;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\HearsHowEachStackIs;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheLaunchReads;
use Modules\Operator\Internal\ViewModels\WhatTheLaunchWas;
use Modules\Operator\Internal\ViewModels\WhatTheOneLineSays;
use Modules\Operator\Internal\WhatEachStackLastSaid;
use Modules\Operator\Internal\WhatItListensWith;
use Modules\Operator\Internal\WhatTheSharingDid;
use Modules\Operator\Internal\WhereAStackIs;
use Modules\Operator\Internal\WhereAStackOpens;
use Modules\Operator\Internal\WhereTappingLeads;
use Modules\Operator\Internal\WhereTheFirstRunIs;
use Modules\Operator\Internal\WhetherItIsHeld;
use Modules\Stacks\Api\AStacksScreen;
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
 * **It draws what the device holds, then listens.** A usable frame is owed
 * without waiting for a reading, so the first frame is the configuration and
 * the words kept for each stack, with when each was heard. After it, the list
 * holds a subscription to each stack it is signed into as the operator, through
 * {@see HearsHowEachStackIs}, and the words those say replace the kept ones.
 *
 * **It carries `#[Lazy]`** because `Stacks::configured()` waits on the platform
 * store. A keychain read is a call across a process boundary, and on Android it
 * can be a binder call that waits on a locked store — on a cold start on a
 * device that has just been powered on, above all.
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
    use OffersTheAppsSettings;
    use HearsHowEachStackIs;

    public ?Launch $launched = null;

    /** What became of the last attempt to hand a report over, as a key, or empty. */
    public string $sharingWent = '';

    /** What to do about it, beside {@see $sharingWent}. */
    public string $sharingRemedy = '';

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
    public WhereTheFirstRunIs $firstRunAt = WhereTheFirstRunIs::WhatThisIs;

    /**
     * What became of the phone's saved data when the app opened, once asked.
     *
     * Held so that the opening is asked once per screen: the seal says a key
     * was made just now exactly once, and a frame that asked again would lose
     * the one line that says what was cleared.
     */
    public ?WhatWasKeptAtOpening $saved = null;

    public function __construct(
        private readonly Stacks $stacks,
        private readonly SecureStorage $storage,
        private readonly Sharing $sharing,
        private readonly Standings $standings,
        private readonly Clock $clock,
        private readonly Opening $opening,
        private readonly ClearingWhatCannotBeRead $clearing,
        private readonly KeepingTheLastReading $keeping,
        private readonly Hearing $hearing,
        private readonly Capture $capture,
        private readonly RemovingAStack $removing,
        private readonly WhereAStackOpens $landing,
        protected readonly TheAppsSettings $settings,
    ) {}

    /**
     * The opening: its housekeeping first, then on to where the operator left
     * off, the first time the list is built in a run. An opening that cleared
     * what the phone kept stays on the list, which says so once.
     */
    public function mount(): void
    {
        $cleared = $this->savedDataWasCleared();
        $lands = $this->landing->theOpening();

        if (! $cleared && $lands !== '') {
            $this->navigate($lands);
        }
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
     * Leave the sequence, landing on why the app reaches the local network.
     *
     * Leaving has to land on pairing rather than on nothing, and this is why
     * the exit is a step rather than a route: an operator who skipped arrives
     * where the sequence was going, on the screen they were already on. It
     * lands one step short of pairing, on the app's reason for the local
     * network, because pairing is where the platform asks and nobody is to meet
     * that prompt unexplained.
     */
    public function skipAhead(): void
    {
        $this->firstRunAt = WhereTheFirstRunIs::TheLocalNetwork;
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
     * screen learns is whether to say *sign in needed*. An address will
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
     * What this stack's one line last said, with when it was heard.
     *
     * The app opens on how each stack stands. The ordering `N2` calls its
     * whole design is *is anything wrong*, then *what*, then *may I fix it from
     * here*, and a list of machines and the names their owner gave them
     * answers none of it.
     *
     * **The core's one line, so every surface says the same word.** The stack's
     * own screen hears it on the event stream and keeps each word it hears in
     * {@see Standings}, and this row says that word as a single word, where
     * that screen says it in a sentence.
     *
     * **Read from the store, on every frame.** A frame is not where a socket
     * is opened, so the word comes out of the store, which makes every one of
     * them a retained reading that carries when it was heard. The list's own
     * subscriptions keep that store fresh while the list is open, so a word
     * heard a moment ago is said as one.
     *
     * The age cannot be dropped on the way here. `Reading::either()` hands the
     * word and the moment to the same arm, so a row showing a word without an
     * age would have to have been given the age and thrown it away.
     */
    public function lastKnownOf(Stack $stack): WhatTheOneLineSays
    {
        return new WhatEachStackLastSaid($this->standings, $this->clock)->of($stack);
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
     * To the sign-in screen where this device holds no session, and where it does,
     * to whichever reading belongs to whoever is signed in. That is the whole of
     * what the list is *for*: somebody already signed in wants to see their machine,
     * not to be asked for a password they already gave.
     *
     * **Whose it is decides which reading**, and this is the launch half of that.
     * Signing in already led where the subject said, but a session outlives the app
     * being closed — so a list that remembered only *that* one was held handed the
     * operator's machine report to a member on every launch after the first, which
     * is the one reading a member is never meant to be given. The subject was in the
     * store the whole time and nothing read it there.
     *
     * **The subject without the session**, through
     * {@see \Modules\Kernel\Api\Resumed::whoseItIs()}. This screen speaks to no
     * stack and has no use for a credential, and the shortest way to keep one out of
     * it is to ask a question whose answer does not contain one — which is the same
     * discipline {@see isSignedInto()} keeps by dropping what it is handed.
     *
     * Decided here rather than in the template, so the three URIs have one spelling
     * each and the branch is somewhere a test can drive it. Spelled in a `match`
     * rather than behind {@see WhereTappingLeads}, because the rule that walks this
     * app's navigation reads an accessor's own body for the routes it calls, and a
     * road built elsewhere is one it reports as leading nowhere.
     */
    public function tappingGoesTo(Stack $stack): string
    {
        $leads = WhereTappingLeads::for($this->storage, $stack->id());

        return match ($leads) {
            WhereTappingLeads::TheSignIn => $this->signInAt($stack),
            WhereTappingLeads::TheReport => $this->landing->onItsLastTab($stack),
            WhereTappingLeads::WhatTheyAreOwed => AStacksScreen::Owed->forTheStack($stack->id()),
        };
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
     * Whether the phone cleared what it kept when the app opened, which is said once.
     *
     * The opening's housekeeping, done on the first frame of this screen, which
     * is built only past the lock: nothing kept is read, cleared or drawn while
     * the app is locked. A removal the app was stopped in the middle of is
     * finished first; then the seal is asked, before anything kept is opened,
     * and a key made afresh clears every store; then every reading kept longer
     * than readings are kept for is let go of.
     */
    public function savedDataWasCleared(): bool
    {
        if (! $this->saved instanceof WhatWasKeptAtOpening) {
            $this->removing->finishWhatWasLeft();
            $this->saved = $this->clearing->onOpening();
            $this->keeping->forgetTheOld($this->clock->now());
        }

        return $this->saved === WhatWasKeptAtOpening::Cleared;
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
     * Held rather than asked again, because each answer is a question put to
     * the network. The lock is not among the answers: this screen is built only
     * once the lock has opened.
     *
     * The template branches on `->met` before it draws anything else. A launch
     * that decided *no network* and then drew the machine names and their last
     * words would leave somebody tapping a stack their phone cannot reach.
     * `->remedy` is stated beside it rather than folded into it, because an
     * obstacle owes both: what happened is a fact about the world and what to
     * do about it is advice.
     */
    public function howItOpened(): WhatTheLaunchWas
    {
        $this->launched ??= $this->opening->found();

        return $this->launched->either(
            unpaired: static fn(): WhatTheLaunchWas => new HowTheLaunchReads()->unpaired(),
            blocked: static fn(Obstacle $why): WhatTheLaunchWas => new HowTheLaunchReads()->blockedBy($why),
            ready: static fn(StackId $stack): WhatTheLaunchWas => new HowTheLaunchReads()->readyFor($stack),
        );
    }

    protected function listensWith(): WhatItListensWith
    {
        return new WhatItListensWith($this->hearing, $this->clock, $this->capture, $this->standings, $this->keeping);
    }
}
