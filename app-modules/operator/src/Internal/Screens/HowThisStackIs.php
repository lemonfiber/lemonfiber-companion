<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Health\Api\Queries\InCategory;
use Modules\Health\Api\Queries\WorstFirst;
use Modules\Kernel\Api\Asking;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Operator\Internal\WhatOneFindingSays;
use Modules\Operator\Internal\WhatTheStackTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Modules\Operator\Internal\WhichFamilyToRead;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What one stack is doing, which is what the whole app is for.
 *
 * Pairing and signing in are both means to this end. `N1-R2` says an operator
 * away from the machine can see whether their stack is doing what it should,
 * and until this screen existed the app could get in and had nothing to show.
 *
 * **It asks once, when the frame is built, and holds what came back.** `N1-R17`
 * says a screen is not a poller: a home network and a machine that may be
 * asleep are the wrong things to talk to four times a second, and `F4` says
 * a frame is not where a socket is opened. The answer is a value on this
 * screen, so every accessor below reads what one asking produced rather than
 * asking again.
 *
 * **Asking again is the operator's to decide**, which is what {@see again()}
 * is and why there is no timer beside it. Somebody who has just gone and
 * restarted a service wants to know whether it took, and a screen that could
 * only be re-asked by leaving it and coming back teaches them to distrust what
 * it says. `N1-R17` is about the app not talking to a machine unprompted; a tap
 * is a prompt.
 *
 * **It reads the session back rather than being handed one.** A screen given a
 * session is a screen that has to be navigated to with one, which is a session
 * in a route — and `N1-R8` keeps them out of URLs for the reason a proxy log
 * gives. The keychain already holds one per stack; this asks it, and an
 * operator whose session has ended meets the sign-in screen rather than an
 * error.
 *
 * **`#[Concealed]`, because a report names what is wrong with somebody's
 * machine.** `N4-R18` is written about credentials and pairing material, and a
 * diagnostic report is the other thing on this app's screens worth keeping out
 * of the task switcher: it says which services are down, which disk is full,
 * and whether the tunnel is leaking.
 */
#[Lazy]
#[Concealed]
final class HowThisStackIs extends NativeComponent
{
    /**
     * What came back, once the frame has asked.
     *
     * `protected` rather than private, which is what `NativeComponent`'s
     * property syncing needs to reach — it assigns from the parent class, so a
     * private member of a subclass becomes a dynamic property and the screen
     * silently stops holding what it thinks it holds.
     */
    protected ?WhatTheStackTurnedOutToBe $answered = null;

    /**
     * Which family of checks the operator is reading, or nothing for all of them.
     *
     * `N2-R9` asks that stuck downloads, provider health, disk pressure and
     * VPN verification each be *reachable*. They are all in the list already,
     * which is reachable in the sense that scrolling is reachable — and an
     * operator who opened the app because the household said nothing was
     * downloading should not have to read past eight families to find the
     * queue.
     *
     * A key rather than a {@see Category}, because this is a screen's own state
     * and `NativeComponent` reflects over what it can serialise. The narrowing
     * turns it back into a case, and a value that is not one is read as no
     * narrowing at all — a tap that lands wrong shows the whole report rather
     * than nothing.
     *
     * **Null rather than an empty string for *not narrowed*.** The two behave
     * identically — neither names a case, so both widen out — and that is the
     * problem: with a sentinel, *no narrowing* and *a key nobody recognises*
     * are the same state reached two ways, and no test can tell them apart.
     * The mutation gate proved it, over every tap sequence of length three: a
     * run with the empty string replaced by an arbitrary word produced
     * byte-identical screens. A value nothing can distinguish is a value
     * nothing can defend, which is `HowLongAgo`'s argument about a floor that
     * had three right answers.
     */
    protected ?string $reading = null;

    public function __construct(
        private readonly Asking $asking,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, so there is one
     * answer to *which machine* and it is the one the URI names — the same
     * argument {@see SignIntoAStack::stack()} makes, and the same refusal for a
     * route naming a stack this device has forgotten.
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

    /** What the run amounts to, as a key, or the empty string where it did not run. */
    public function overall(): string
    {
        return $this->answer()->overall;
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
     * Each finding, as a row a template can read, in the order they were made.
     *
     * Rows rather than {@see Finding}s, because a finding answers what the
     * check said through an `either()` and Blade has no way to call one. The
     * fold happens once per row here rather than being written into the
     * template — which could not write it — and `N2-R3`'s words reach the
     * screen as a result.
     *
     * @return list<WhatOneFindingSays>
     */
    public function findings(): array
    {
        // Collected by hand, as `WorstFirst` does and for its reason: `Findings`
        // always holds a list, so `iterator_to_array`'s `preserve_keys` cannot
        // be wrong here — and an argument that cannot change the answer is a
        // line no test can defend.
        // `N2-R2` — worst first, and ordered *here* rather than trusted to
        // arrive that way. A list rendered straight from the envelope comes in
        // the order the checks ran, which looks ordered and is not: the failure
        // is invisible on any report whose worst finding happens to have run
        // first. `operator/src/README.md` recorded this gate against the day a
        // findings screen existed, and this is the day.
        $run = new WorstFirst()->over($this->narrowed($this->answer()->findings));
        $rows = [];

        foreach ($run as $finding) {
            // The whole run, not just the row: a finding the engine attributed
            // to another is shown by that other one's title, and the title is
            // only findable here.
            $rows[] = WhatOneFindingSays::in($finding, $run);
        }

        return $rows;
    }

    /** How many are shown, which is what the empty state asks. */
    public function howMany(): int
    {
        return $this->narrowed($this->answer()->findings)->count();
    }

    /**
     * The families this run has something to say about, in the engine's order.
     *
     * Only the ones with findings. A row of nine chips where six are empty is
     * six taps that lead to a blank screen, and an operator learns from the
     * first one that the row is not worth reading.
     *
     * @return list<WhichFamilyToRead>
     */
    public function families(): array
    {
        $families = [];

        foreach (Category::cases() as $family) {
            $found = new InCategory($family)->over($this->answer()->findings);

            if ($found->count() === 0) {
                continue;
            }

            $families[] = WhichFamilyToRead::of($family, $found->count(), $this->reading === $family->value);
        }

        return $families;
    }

    /** Whether the operator is reading one family rather than the whole run. */
    public function isNarrowed(): bool
    {
        return $this->narrowing() instanceof Category;
    }

    /**
     * Read one family, or read all of them again by naming the one in view.
     *
     * Tapping the family already being read widens back out, which is the
     * gesture somebody makes without being told: there is no separate "all"
     * chip to find, and the way back is the way in.
     */
    public function read(string $family): void
    {
        $this->reading = $this->reading === $family ? null : $family;
    }

    /**
     * Ask the stack again, because the operator has just done something.
     *
     * Forgetting what was held rather than asking and comparing: the next read
     * of any accessor rebuilds it, so there is one path to an answer and it is
     * the one every other frame takes. A second path that filled the same field
     * would be the place the two come to disagree.
     *
     * The session is resumed again with it, deliberately. An operator who has
     * been on this screen a while may have had their session end underneath
     * them, and a refresh that reused a session it never re-checked would show
     * them a stale report under a stack they are no longer signed into.
     */
    public function again(): void
    {
        $this->answered = null;
    }

    /**
     * Where one service's scrollback is (`N2-R10`).
     *
     * Takes the name a row is holding rather than a {@see ServiceId}, because a
     * template cannot build one and giving it the chance would put the refusal
     * for a blank name inside a Blade expression. The value is made here, where
     * a blank is a fault in the fold above rather than a screen half-drawn.
     */
    public function logsOf(string $service): string
    {
        return $this->goes()->logsOf(ServiceId::called($service));
    }

    /**
     * Where this machine's screens are.
     *
     * One accessor rather than one per destination. {@see WhereAStackIs} is the
     * only place that knows a stack's routes, and it is built from the stack
     * this screen is already about, so none of them can lead to another
     * machine's. Three separate `somethingAreAt()` methods took this class to
     * the twenty-method ceiling `Q-R64` refuses; the next destination the hub
     * links to now costs no method here at all.
     */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::how-this-stack-is');
    }

    /**
     * The findings this screen is showing, narrowed where the operator asked.
     *
     * `InCategory` narrows and deliberately does not reorder, so this composes
     * with {@see WorstFirst} in the order it reads: narrow, then sort. Doing it
     * the other way would sort rows that are about to be thrown away.
     */
    private function narrowed(Findings $findings): Findings
    {
        $family = $this->narrowing();

        return $family instanceof Category
            ? new InCategory($family)->over($findings)
            : $findings;
    }

    /**
     * The family being read, as a case, or nothing.
     *
     * A stored value that names no case is read as no narrowing. The screen's
     * own state is the only thing that writes it, but `tryFrom` is what makes
     * that a fact rather than a hope — and the failure it prevents is a blank
     * report where the operator expected a report.
     */
    private function narrowing(): ?Category
    {
        return $this->reading === null ? null : Category::tryFrom($this->reading);
    }

    /**
     * What came back, asked once and held.
     *
     * The asking happens here rather than in a constructor because a
     * constructor runs before the route parameter is set — {@see Stack()} would
     * have nothing to read. Held rather than recomputed because every accessor
     * above calls this, and a screen that asked per accessor would open six
     * connections to render one frame.
     */
    private function answer(): WhatTheStackTurnedOutToBe
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
    private function ask(): WhatTheStackTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheStackTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatTheStackTurnedOutToBe => WhatTheStackTurnedOutToBe::signedOut(),
        );
    }

    /** What the stack said, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): WhatTheStackTurnedOutToBe
    {
        return $this->asking->about($stack, $session)->either(
            said: static fn(Report $report): WhatTheStackTurnedOutToBe => WhatTheStackTurnedOutToBe::said($report),
            met: static fn(Obstacle $why): WhatTheStackTurnedOutToBe => WhatTheStackTurnedOutToBe::met($why),
        );
    }
}
