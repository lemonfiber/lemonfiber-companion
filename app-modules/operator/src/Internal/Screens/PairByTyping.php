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
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/**
 * Pairing a stack by typing the code, rather than by pointing a camera at it.
 *
 * `N1-R6` requires both roads and `N4-R3` says why this one is not a courtesy:
 * it is the road on a device with no camera and on one whose operator declined
 * the permission, and a permission with no working alternative is a permission
 * the app has made compulsory.
 *
 * **`N1-R50` is the shape of this screen.** Typed entry has no software
 * comparison in it — nothing scanned the digest, so nothing can check the
 * characters against anything — and the requirement fills that gap with the one
 * comparison people are good at: the app shows the fingerprint it read in a
 * form a person can hold in their head, and the operator says whether it is the
 * one their stack is displaying. Until they do, this screen has a stack it
 * refuses to pair.
 *
 * **The state it keeps is what the operator typed, and nothing derived.**
 * `N1-R38` keeps what a person did on a screen; the reading of it is computed
 * per frame by {@see ReadingACode}, so the sentence shown can never be about a
 * code that has since been edited. That also makes the whole of this screen's
 * behaviour askable without a device — the interesting part is a pure function
 * of two strings and a clock.
 *
 * **`#[Concealed]`, decided rather than demanded.** The rule that insists on it
 * reads constructor types, and this screen is handed ports rather than a
 * `Fingerprint`, so nothing would have failed without the attribute. `N4-R18`
 * names pairing material by name and this screen has it on the glass — the
 * address, the digest and the comparable form together — which is precisely the
 * frame the task switcher keeps and a screen recording captures.
 *
 * **`#[Lazy]` because two of the three things it is handed are ports.** The
 * store this reaches on pairing is a keychain, and a keychain read is a call
 * across a process boundary that can wait on a locked store — `F4`'s argument,
 * and the same one {@see NoStackYet} carries.
 */
#[Lazy]
#[Concealed]
final class PairByTyping extends NativeComponent
{
    /**
     * The pairing code, as it stands in the field.
     *
     * `protected` rather than public, and the template reads it through
     * {@see typed()}. A mutable public property is refused for a reason, and
     * `NativeComponent::__syncProperty()` assigns from the parent class — which
     * reaches a protected member of a subclass and does not reach a private
     * one, so this is exactly as open as the framework needs and no more.
     *
     * It also leaves the template with one idiom. A screen exposing some state
     * as bare variables and the rest as `$this->something()` is a screen where
     * a reader has to know which is which.
     *
     * A bare string because a field holds characters rather than a value.
     * {@see Pairing} is what it becomes, and it becomes one in exactly one
     * place.
     */
    protected string $typed = '';

    /** What the operator is calling this machine (`N1-R11`). */
    protected string $called = '';

    /** What became of the pairing, once they have confirmed one. */
    protected HowThePairingWent $went = HowThePairingWent::NotYet;

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
     * screen opened, which is what makes `N1-R49` true of a screen somebody
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

    /** Whether it was material and its moment has passed (`N1-R49`). */
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
     * problem: `N1-R50` needs a fingerprint that has actually been shown, and
     * `N1-R11` needs a name the operator picked, since the two things the
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
     * here, which is what keeps `N1-R50` structural: this screen cannot make a
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

    /** The frame, by name. */
    public function render(): View
    {
        return view('operator::pair-by-typing');
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

        return $this->stacks->remember($stack)->either(
            remembered: static fn(): HowThePairingWent => HowThePairingWent::Paired,
            refused: static fn(WhyAStackCannotBeRemembered $why): HowThePairingWent => HowThePairingWent::refused($why),
        );
    }
}
