<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\FingerprintWasConfirmed;
use Modules\Connection\Api\HowThePairingWent;
use Modules\Connection\Api\Introducing;
use Modules\Connection\Api\WhatTheCodeSaysSoFar;
use Modules\Connection\Api\WhereTheCodeGot;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowItWasRead;
use Modules\Kernel\Api\Pairing;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/**
 * Pairing a stack by typing the code, rather than by pointing a camera at it.
 *
 * Both roads are required, and this one is not a courtesy:
 * it is the road on a device with no camera and on one whose operator declined
 * the permission, and a permission with no working alternative is a permission
 * the app has made compulsory.
 *
 * **The confirmation is the shape of this screen.** Typed entry has no software
 * comparison in it — nothing scanned the digest, so nothing can check the
 * characters against anything — and the requirement fills that gap with the one
 * comparison people are good at: the app shows the fingerprint it read in a
 * form a person can hold in their head, and the operator says whether it is the
 * one their stack is displaying. Until they do, this screen has a stack it
 * refuses to pair.
 *
 * **The state it keeps is what the operator typed, and nothing derived.**
 * A screen keeps what a person did; the reading of it is computed
 * per frame by {@see ReadingACode}, so the sentence shown can never be about a
 * code that has since been edited. That also makes the whole of this screen's
 * behaviour askable without a device — the interesting part is a pure function
 * of two strings and a clock.
 *
 * **`#[Concealed]`, decided rather than demanded.** The rule that insists on it
 * reads constructor types, and this screen is handed ports rather than a
 * `Fingerprint`, so nothing would have failed without the attribute. The capture rule
 * names pairing material by name and this screen has it on the glass — the
 * address, the digest and the comparable form together — which is precisely the
 * frame the task switcher keeps and a screen recording captures.
 *
 * **`#[Lazy]` because two of the three things it is handed are ports.** The
 * store this reaches on pairing is a keychain, and a keychain read is a call
 * across a process boundary that can wait on a locked store — `F4`'s argument,
 * and the same one {@see YourStacks} carries.
 */
#[Lazy]
#[Concealed]
final class PairByTyping extends NativeComponent
{
    /**
     * The pairing code, as it stands in the field.
     *
     * `protected` rather than public. A mutable public property is refused for
     * a reason, and `NativeComponent::__syncProperty()` assigns from the parent
     * class — which reaches a protected member of a subclass and does not reach
     * a private one, so this is exactly as open as the framework needs and no
     * more.
     *
     * **That is why `render()` hands it to the view by name.**
     * `native:model="typed"` expands to `:value="$typed"`, a bare variable in
     * the compiled view, and the package fills the view's data from a
     * component's *public* properties — so a protected one arrives undefined,
     * which is a warning rather than a stop and draws an empty field.
     *
     * A bare string because a field holds characters rather than a value.
     * {@see Pairing} is what it becomes, and it becomes one in exactly one
     * place.
     */
    protected string $typed = '';

    /** What the operator is calling this machine. */
    protected string $called = '';

    /** What became of the pairing, once they have confirmed one. */
    protected HowThePairingWent $went = HowThePairingWent::NotYet;

    /**
     * Which stack was paired, so the screen can lead to it.
     *
     * Written at the moment the stack is made rather than read back from the
     * store afterwards, which would be asking *which one did I just add* of a
     * list that does not say. `protected` for `NativeComponent`'s property
     * syncing, and the identifier rather than the {@see Stack} because a screen
     * that held a stack would be holding an address.
     */
    protected string $paired = '';

    public function __construct(
        private readonly Introducing $introducing,
        private readonly Stacks $stacks,
        private readonly Clock $clock,
    ) {}

    /** What the operator has typed into the code field. */
    public function typed(): string
    {
        return $this->typed;
    }

    /** What they are calling this machine. */
    public function called(): string
    {
        return $this->called;
    }

    /** What became of the pairing, once they have confirmed one. */
    public function went(): HowThePairingWent
    {
        return $this->went;
    }

    /**
     * How far the code in the field has got, as of this frame.
     *
     * Recomputed rather than held, so the fingerprint on the screen is always
     * the fingerprint of what is in the box. A held reading is how an operator
     * ends up confirming a form that belonged to the code they typed before the
     * one they are looking at.
     *
     * The clock is asked at the moment of reading rather than at the moment the
     * screen opened, which is what makes expiry true of a screen somebody
     * leaves open: a code that was still good when they started typing expires
     * while they are still in the field, and the sentence changes under them.
     */
    public function code(): WhatTheCodeSaysSoFar
    {
        return WhatTheCodeSaysSoFar::read($this->typed, HowItWasRead::Typed, $this->clock);
    }

    /** Whether there is a fingerprint on the screen for the operator to check. */
    public function isComparing(): bool
    {
        return $this->code()->got() === WhereTheCodeGot::Comparing;
    }

    /** Whether the field is empty, which is not the same as being wrong. */
    public function isWaiting(): bool
    {
        return $this->code()->got() === WhereTheCodeGot::Waiting;
    }

    /** Whether what was typed is not pairing material this app can read. */
    public function isUnreadable(): bool
    {
        return $this->code()->got() === WhereTheCodeGot::Unreadable;
    }

    /** Whether it was material and its moment has passed. */
    public function hasExpired(): bool
    {
        return $this->code()->got() === WhereTheCodeGot::Expired;
    }

    /**
     * The key for the line under the code field, where a refusal appears.
     *
     * One sentence beside the field rather than a block above it, because the
     * field is where the mistake is and a message next to it is the one
     * somebody reads. Which sentence is {@see WhereTheCodeGot}'s to answer —
     * four states, four keys, and a `match` with no default arm.
     *
     * A key rather than the words: `A4` refuses a class reaching for a
     * translator it never asked for, and the template is where `__()` belongs
     * anyway.
     */
    public function supportingTheCode(): string
    {
        return $this->code()->got()->saidUnderTheField();
    }

    /** The form the operator compares against what their stack is displaying. */
    public function toCompare(): string
    {
        return $this->code()->toCompare();
    }

    /**
     * Whether the control that completes the pairing may be offered at all.
     *
     * Both halves, because both are required and neither is the other's
     * problem: the confirmation needs a fingerprint that has actually been shown, and
     * a device needs a name the operator picked, since the two things the
     * material carries are an address and a digest and neither is a name.
     *
     * Offering it and refusing the tap would be the worse design — a control
     * that does nothing teaches an operator that the app is broken, and they
     * have no way to discover which of the two fields it is waiting on.
     */
    public function mayPair(): bool
    {
        return $this->isComparing() && trim($this->called) !== '';
    }

    /**
     * The operator says the form on the screen is the one on their stack.
     *
     * The confirmation is built by the value that rendered the form rather than
     * here, which is what keeps the confirmation structural: this screen cannot make a
     * {@see FingerprintWasConfirmed}, so it cannot make one about a code it did
     * not show.
     *
     * A pairing that cannot be written down has not happened, so the refusal
     * becomes the screen's state rather than being dropped — an operator told
     * "paired" who finds nothing next launch was misled by an app that knew.
     */
    public function confirm(): void
    {
        $this->went = $this->code()->confirmedByTheOperator(
            confirmed: fn(Pairing $said, FingerprintWasConfirmed $by): HowThePairingWent => $this->remembered($said, $by),
            notYet: static fn(): HowThePairingWent => HowThePairingWent::NotYet,
        );
    }

    /**
     * The headline: what this screen is for, or what became of the pairing.
     *
     * The two keys spelled here are this screen's own, and they are the only
     * two it spells. Everything after the operator has confirmed something is
     * {@see HowThePairingWent}'s to name, derived from the case — so a fourth
     * outcome needs no edit here and no branch in the template.
     *
     * **The waiting pair cannot come off the outcome.** What a screen says
     * before anything has happened is what that screen is *for*, and the two
     * pairing roads are for different things: this one and its sibling share
     * every outcome and share neither opening line.
     */
    public function headline(): string
    {
        return $this->went->isNotYet() ? HowItWasRead::Typed->askedFor() : $this->went->said();
    }

    /** The line under it: how to get started, or what to do about what happened. */
    public function supporting(): string
    {
        return $this->went->isNotYet() ? HowItWasRead::Typed->howToStart() : $this->went->remedy();
    }

    /**
     * Where an operator who has just paired a stack goes next.
     *
     * The sign-in screen for the stack they paired, because pairing is not
     * signing in: they have introduced the machine and hold no session for it.
     * Until this existed the screen said *"you can reach it from the main
     * screen"* and left them to go and do it.
     */
    public function onwardsTo(): string
    {
        return WhereAStackIs::rememberedAs($this->paired)->signIn();
    }

    /**
     * Where the list of machines is.
     *
     * Read off the case the provider registers from, for the reason every
     * other route here is: a rename cannot leave this button pointing at
     * nothing.
     */
    public function theListIsAt(): string
    {
        return AScreenWithoutAStack::TheList->value;
    }

    /** The frame, by name. */
    public function render(): View
    {
        return view('operator::pair-by-typing', [
            'typed' => $this->typed,
            'called' => $this->called,
        ]);
    }

    /**
     * Introduce the stack and write it down, saying which of those happened.
     *
     * Named rather than inlined into the closure above because a closure that
     * reaches two ports and an outcome is a closure nobody reads twice — and
     * because the analyser refuses a checked exception raised inside one, which
     * is what {@see Introducing::confirmed()} does when a confirmation names
     * another certificate.
     *
     * The name is not trimmed here. {@see StackName::of()} trims on the way in,
     * which is where the one copy of that decision belongs, and a second trim
     * was a line no test could distinguish from its absence. `mayPair()`'s trim
     * is a different question — whether anything was typed at all — and is
     * load-bearing.
     */
    private function remembered(Pairing $said, FingerprintWasConfirmed $by): HowThePairingWent
    {
        $stack = $this->introducing->confirmed($said, StackName::of($this->called), $by);
        $this->paired = $stack->id()->stored();

        return $this->stacks->remember($stack)->either(
            remembered: static fn(): HowThePairingWent => HowThePairingWent::Paired,
            refused: static fn(WhyAStackCannotBeRemembered $why): HowThePairingWent => HowThePairingWent::refused($why),
        );
    }
}
