<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\AFormatChoiceMade;
use Modules\Kernel\Api\AHeldChoice;
use Modules\Kernel\Api\AnUpgradeDescribed;
use Modules\Kernel\Api\APresetToChoose;
use Modules\Kernel\Api\ChoosingQuality;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\TheQualityChosen;
use Modules\Kernel\Api\TheUpgrade;
use Modules\Kernel\Api\UpgradingTheLibrary;
use Modules\Kernel\Api\WhatBecameOfTheChoice;
use Modules\Kernel\Api\WhatTheChoiceCameTo;
use Modules\Kernel\Api\WhatTheUpgradeCameTo;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowAnUpgradeReads;
use Modules\Operator\Internal\Presenters\HowTheQualityReads;
use Modules\Operator\Internal\ViewModels\AFormatChoiceAsShown;
use Modules\Operator\Internal\ViewModels\TheQualityTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheUpgradeTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/**
 * How good this machine's media should be: what is in force, choosing, and upgrading what is already here.
 *
 * **Every preset is the stack's word.** The screen holds no list of presets
 * or kinds of media: what is drawn is what came back, and what is chosen is
 * what the operator typed, which the stack takes or refuses.
 *
 * **Seeing is never agreeing.** A held choice is drawn with why it was held,
 * and choosing it anyway is a tap on {@see confirm()}, which carries the
 * {@see AHeldChoice} only the held answer made. An upgrade is described first
 * and carried out by a second tap on {@see upgrade()}, which carries the
 * {@see AnUpgradeDescribed} only a description made.
 *
 * Putting a preset back over a hand-edited configuration is not offered: its
 * whole effect is overwriting what the operator changed.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class ChoosingHowGood extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * The preset typed, or the format for music.
     *
     * `public`, for the reason {@see WhatThisStackIsSetTo::$typed} gives, and
     * handed to the view by name in {@see render()} for the same reason.
     */
    public string $preset = '';

    /** The kind of media typed, or nothing for everything; `public` for the reason above. */
    public string $kind = '';

    /** What is in force, once the frame has asked or a choice came back. */
    public ?TheQualityTurnedOutToBe $answered = null;

    /** What choosing a format for music did, where that is what came back. */
    public ?AFormatChoiceAsShown $music = null;

    /** The choice the stack held, while it waits on the operator's yes. */
    public ?AHeldChoice $held = null;

    /** What upgrading came to, once it was asked about. */
    public ?TheUpgradeTurnedOutToBe $upgrading = null;

    /** The upgrade the stack described, while it waits on the operator's yes. */
    public ?AnUpgradeDescribed $described = null;

    public function __construct(
        private readonly ChoosingQuality $choosing,
        private readonly UpgradingTheLibrary $upgrades,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, for
     * {@see WhatIsRunningHere::stack()}'s reason.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /**
     * Ask the machine again, and forget every answer and every yes on offer.
     *
     * A yes is forgotten with the answer it was about, so none can be given
     * against a screen that is no longer showing what it was built from.
     */
    public function again(): void
    {
        $this->answered = null;
        $this->music = null;
        $this->held = null;
        $this->upgrading = null;
        $this->described = null;
    }

    /**
     * Choose what was typed.
     *
     * Nothing typed is nothing asked, rather than a blank the stack would refuse.
     */
    public function choose(): void
    {
        if (trim($this->preset) === '') {
            return;
        }

        $asked = APresetToChoose::named($this->preset, $this->kind);

        $this->chose($asked, fn(ChoosingQuality $choosing, Stack $stack, Session $session): WhatTheChoiceCameTo
            => $choosing->choose($stack, $session, $asked));
    }

    /** Choose the held preset after all, having been shown why it was held. */
    public function confirm(): void
    {
        $held = $this->held;

        if (! $held instanceof AHeldChoice) {
            return;
        }

        $this->chose($held->asked(), static fn(ChoosingQuality $choosing, Stack $stack, Session $session): WhatTheChoiceCameTo
            => $choosing->confirm($stack, $session, $held));
    }

    /** Ask what upgrading what is already here would come to, fetching nothing. */
    public function describe(): void
    {
        $this->upgraded(static fn(UpgradingTheLibrary $upgrades, Stack $stack, Session $session): WhatTheUpgradeCameTo
            => $upgrades->whatItWouldComeTo($stack, $session));
    }

    /** Carry out the upgrade that was described. */
    public function upgrade(): void
    {
        $described = $this->described;

        if (! $described instanceof AnUpgradeDescribed) {
            return;
        }

        $this->upgraded(static fn(UpgradingTheLibrary $upgrades, Stack $stack, Session $session): WhatTheUpgradeCameTo
            => $upgrades->upgrade($stack, $session, $described));
    }

    /** Whether a held choice waits on the operator's yes. */
    public function mayConfirm(): bool
    {
        return $this->held instanceof AHeldChoice;
    }

    /** Whether a described upgrade waits on the operator's yes. */
    public function mayUpgrade(): bool
    {
        return $this->described instanceof AnUpgradeDescribed;
    }

    /** Where this machine's screens are. */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::choosing-how-good', ['preset' => $this->preset, 'kind' => $this->kind]);
    }

    /** What is in force, asked once per frame. */
    public function answer(): TheQualityTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /**
     * The one path to the stack that choosing and confirming take.
     *
     * The yes on offer is replaced by whatever this answer offers: a held
     * answer offers one, and every other answer none.
     *
     * @param Closure(ChoosingQuality, Stack, Session): WhatTheChoiceCameTo $asking
     */
    private function chose(APresetToChoose $asked, Closure $asking): void
    {
        $stack = $this->stack();
        $this->held = null;
        $this->music = null;

        $this->answered = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheQualityTurnedOutToBe => $asking($this->choosing, $stack, $session)->either(
                inForce: fn(TheQualityChosen $chosen): TheQualityTurnedOutToBe => $this->inForce($asked, $chosen),
                forMusic: fn(AFormatChoiceMade $made): TheQualityTurnedOutToBe => $this->forMusic($made),
                met: fn(Obstacle $why): TheQualityTurnedOutToBe => $this->stoppedBy($why, $stack),
            ),
            notHeld: static fn(): TheQualityTurnedOutToBe => new HowTheQualityReads()->signedOut(),
        );
    }

    /** What is in force after a choice, with the yes it offers where it was held. */
    private function inForce(APresetToChoose $asked, TheQualityChosen $chosen): TheQualityTurnedOutToBe
    {
        if ($chosen->became() === WhatBecameOfTheChoice::Held) {
            $this->held = AHeldChoice::of($asked, $chosen);
        }

        return new HowTheQualityReads()->this($chosen);
    }

    /**
     * What choosing for music did, and what is in force read again.
     *
     * The music answer carries the format and not the rest of what is in
     * force, so the listing is asked for afresh rather than patched.
     */
    private function forMusic(AFormatChoiceMade $made): TheQualityTurnedOutToBe
    {
        $this->music = new HowTheQualityReads()->music($made);

        return $this->ask();
    }

    /** A choice that met something, having let go of a session the stack refused. */
    private function stoppedBy(Obstacle $why, Stack $stack): TheQualityTurnedOutToBe
    {
        $this->letGoOfTheSession($why, $stack);

        return new HowTheQualityReads()->met($why);
    }

    /**
     * The one path to the stack that describing and upgrading take.
     *
     * @param Closure(UpgradingTheLibrary, Stack, Session): WhatTheUpgradeCameTo $asking
     */
    private function upgraded(Closure $asking): void
    {
        $stack = $this->stack();
        $this->described = null;

        $this->upgrading = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheUpgradeTurnedOutToBe => $asking($this->upgrades, $stack, $session)->either(
                said: function (TheUpgrade $upgrade): TheUpgradeTurnedOutToBe {
                    if (! $upgrade->wasCarriedOut()) {
                        $this->described = AnUpgradeDescribed::by($upgrade);
                    }

                    return new HowAnUpgradeReads()->this($upgrade);
                },
                met: function (Obstacle $why) use ($stack): TheUpgradeTurnedOutToBe {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowAnUpgradeReads()->met($why);
                },
            ),
            notHeld: static fn(): TheUpgradeTurnedOutToBe => new HowAnUpgradeReads()->signedOut(),
        );
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): TheQualityTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheQualityTurnedOutToBe => $this->choosing->inForceOn($stack, $session)->either(
                found: static fn(TheQualityChosen $chosen): TheQualityTurnedOutToBe => new HowTheQualityReads()->this($chosen),
                met: fn(Obstacle $why): TheQualityTurnedOutToBe => $this->stoppedBy($why, $stack),
            ),
            notHeld: static fn(): TheQualityTurnedOutToBe => new HowTheQualityReads()->signedOut(),
        );
    }
}
