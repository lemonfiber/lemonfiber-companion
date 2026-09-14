<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\HowThePairingWent;
use Modules\Connection\Api\Introducing;
use Modules\Connection\Api\WhatTheCodeSaysSoFar;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowItWasRead;
use Modules\Kernel\Api\Pairing;
use Modules\Kernel\Api\Scanning;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhatTheCameraSaw;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;
use Modules\Kernel\Api\WhyNothingWasScanned;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/**
 * Pairing a stack by pointing the camera at the code on its screen.
 *
 * `N1-R6`'s first road, and the one `ADR-0018` built the design around: the
 * digest arrives in the payload rather than being read off a screen by a
 * person, so the comparison happens in software and **nobody compares hex**.
 * That is why this screen has no confirmation step and {@see PairByTyping} does
 * — the two are not the same flow with a different input widget.
 *
 * **The order is `N4-R2`'s.** The app's own sentence goes up first, in front of
 * a button; the button is what opens the camera; and opening the camera is what
 * raises the platform's prompt, which is `N4-R1`'s point of first use. Nothing
 * here asks on launch, because the screen draws before anything is called.
 *
 * **`N4-R3` is the refusal arm and it is the reason the refusals are told
 * apart.** A declined camera is answered by offering the typed road, which
 * exists; a scanner somebody simply closed is answered by offering another go;
 * a device with no camera is answered by neither, because there is nothing to
 * turn on and nothing to retry. One sentence for the three is the sentence that
 * is wrong for two of them.
 *
 * **The name is asked for before the camera opens, not after.** `N1-R11` needs
 * one, and a screen that scanned first would have to hold the material while
 * somebody types — which is a paired stack waiting on a text field, and the
 * material is the thing `N4-R18` says must not sit on a frame the task switcher
 * keeps. Asking first also means the scan either completes the pairing or does
 * not, with no state in between.
 *
 * **`#[Concealed]`** for that same requirement: the payload this screen holds
 * after a scan is the address and the digest together, which is pairing
 * material by name.
 *
 * **`#[Lazy]`** because two of the three things it is handed are ports, and the
 * store behind one of them is a keychain — a call across a process boundary
 * that can wait on a locked store (`F4`, `N1-R36`).
 */
#[Lazy]
#[Concealed]
final class PairByScanning extends NativeComponent
{
    /**
     * What the operator is calling this machine (`N1-R11`).
     *
     * `protected` rather than public, and the template reads it through
     * {@see called()}. A mutable public property is refused for a reason, and
     * `NativeComponent::__syncProperty()` assigns from the parent class — which
     * reaches a protected member of a subclass and does not reach a private
     * one, so this is exactly as open as the framework needs and no more. It
     * also leaves the template with one idiom rather than two.
     */
    protected string $called = '';

    /**
     * What the camera came back with, or nothing yet.
     *
     * Held rather than recomputed, which is the opposite of {@see PairByTyping}
     * and right for the opposite reason: there is no field to get out of step
     * with. A scan happens once, at a moment the operator chose, and the result
     * is what the screen is about until they scan again.
     */
    protected ?WhyNothingWasScanned $nothingCameBack = null;

    /** What became of the pairing, once a scan has completed one. */
    protected HowThePairingWent $went = HowThePairingWent::NotYet;

    /**
     * Which stack was paired, so the screen can lead to it.
     *
     * Written at the moment the stack is made rather than read back from the
     * store afterwards, which would be asking *which one did I just add* of a
     * list that does not say. `protected` for `NativeComponent`'s property
     * syncing, and the identifier rather than the {@see Stack} because a screen
     * that held a stack would be holding an address (`N1-R15`).
     */
    protected string $paired = '';

    /** Whether what the camera read could not be used as pairing material. */
    protected bool $codeWasUnreadable = false;

    public function __construct(
        private readonly Scanning $camera,
        private readonly Introducing $introducing,
        private readonly Stacks $stacks,
        private readonly Clock $clock,
    ) {}

    /** What the operator is calling this machine. */
    public function called(): string
    {
        return $this->called;
    }

    /** What became of the pairing, once a scan has completed one. */
    public function went(): HowThePairingWent
    {
        return $this->went;
    }

    /** Whether what the camera read could not be used as pairing material. */
    public function codeWasUnreadable(): bool
    {
        return $this->codeWasUnreadable;
    }

    /**
     * Whether the camera may be offered at all.
     *
     * `N1-R11` needs a name and the material carries none — an address and a
     * digest are not two names. Offering the button and refusing the tap would
     * be worse: a control that does nothing teaches an operator the app is
     * broken, and they have no way to discover which field it is waiting on.
     */
    public function mayScan(): bool
    {
        return trim($this->called) !== '';
    }

    /** Whether the last attempt came back with nothing, for any of the three reasons. */
    public function nothingWasScanned(): bool
    {
        return $this->nothingCameBack instanceof WhyNothingWasScanned;
    }

    /**
     * What to do about the camera coming back empty, as a key.
     *
     * Answered by the reason rather than by this screen, which is where the
     * three-into-two mistake was: a boolean choosing between "open Settings"
     * and one generic alternative can only be wrong about the third reason,
     * and it was — a closed scanner was told it could type the code instead
     * rather than that it could open the camera again.
     *
     * The empty string where nothing came back, which is what the template
     * branches on: there is no advice to give about a camera that has not been
     * opened yet, and a key invented for that state would be a catalogue line
     * for a sentence nobody should read.
     */
    public function remedyForTheCamera(): string
    {
        return $this->nothingCameBack?->remedy() ?? '';
    }

    /**
     * The key for what to say about the camera coming back empty.
     *
     * A key rather than the words, so `A4` keeps the translator out of a class
     * that never asked for one, and {@see WhyNothingWasScanned} owns which
     * sentence belongs to which reason.
     */
    public function whyNothingCameBack(): string
    {
        return $this->nothingCameBack?->saidOnTheScreen() ?? '';
    }

    /**
     * Open the camera, and pair with whatever it reads.
     *
     * The whole of the scanned road in one method, because it is one act as far
     * as the operator is concerned: they tap, they point, and either a stack is
     * paired or they are told why not.
     *
     * Guarded rather than trusted, for the reason `mayScan()` exists — a
     * template that offered the control outside its branch would otherwise open
     * the camera and then discard what it read for want of a name.
     */
    public function scan(): void
    {
        if (! $this->mayScan()) {
            return;
        }

        $this->nothingCameBack = null;
        $this->codeWasUnreadable = false;

        $this->camera->forAPairingCode($this->read(...));
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
        return $this->went->isNotYet() ? HowItWasRead::Scanned->askedFor() : $this->went->said();
    }

    /** The line under it: how to get started, or what to do about what happened. */
    public function supporting(): string
    {
        return $this->went->isNotYet() ? HowItWasRead::Scanned->howToStart() : $this->went->remedy();
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

    /** The frame, by name. */
    public function render(): View
    {
        return view('operator::pair-by-scanning');
    }

    /**
     * What to do with what the camera saw.
     *
     * A method rather than a closure at the call site, for the reason the
     * composition root gives about the same shape: a closure reaching two ports
     * and two outcome types is one nobody reads twice, and the analyser refuses
     * a checked exception raised inside one.
     */
    private function read(WhatTheCameraSaw $saw): void
    {
        $saw->either(
            read: fn(string $payload): HowThePairingWent => $this->paired($payload),
            nothing: function (WhyNothingWasScanned $why): HowThePairingWent {
                $this->nothingCameBack = $why;

                return HowThePairingWent::NotYet;
            },
        );
    }

    /**
     * Pair with what was read, or record that it was not pairing material.
     *
     * Parsed through the same named constructor the typed road goes through,
     * which is what keeps `N1-R49`'s expiry and every refusal along the way
     * true of both roads. A second parser here would be the one that stopped
     * being tested.
     *
     * No confirmation is asked for and none is possible: `Introducing::stack()`
     * is the scanned road, and it refuses typed material rather than treating
     * the two alike.
     */
    private function paired(string $payload): HowThePairingWent
    {
        // `material()` rather than `confirmedByTheOperator()`, and the
        // difference is the whole of N1-R50. That method mints a
        // `FingerprintWasConfirmed`, which may only exist where a person
        // compared something; nobody compared anything here, because the digest
        // arrived in the payload. Reaching for it to get at the material would
        // conjure the one value the typed road is built around.
        //
        // Whether the code parsed is asked once, by `material()`, rather than
        // by a guard here as well. The guard was the same decision written
        // twice and the copy could not be told from its absence: both roads out
        // of it answered `NotYet`, so removing it changed nothing a test could
        // see — which is the shape of a line nobody should keep.
        return $this->went = WhatTheCodeSaysSoFar::read($payload, HowItWasRead::Scanned, $this->clock)
            ->material(
                read: fn(Pairing $material): HowThePairingWent => $this->remembered($material),
                notYet: function (): HowThePairingWent {
                    $this->codeWasUnreadable = true;

                    return HowThePairingWent::NotYet;
                },
            );
    }

    /** Introduce the stack and write it down, saying which of those happened. */
    private function remembered(Pairing $said): HowThePairingWent
    {
        $stack = $this->introducing->stack($said, StackName::of($this->called));
        $this->paired = $stack->id()->stored();

        return $this->stacks->remember($stack)->either(
            remembered: static fn(): HowThePairingWent => HowThePairingWent::Paired,
            refused: static fn(WhyAStackCannotBeRemembered $why): HowThePairingWent => HowThePairingWent::refused($why),
        );
    }
}
