<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\AHeldChoice;
use Modules\Kernel\Api\APresetToChoose;
use Modules\Kernel\Api\ChoosingQuality;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheQualityChosen;
use Modules\Kernel\Api\WhatTheChoiceCameTo;
use Modules\Kernel\Api\WhatWasFoundOfTheQuality;
use Override;

/**
 * {@see ChoosingQuality}, answered from what a test put in it.
 *
 * Remembers **which** of the three was called and with what. The port keeps
 * choosing apart from confirming, so a fake counting them together would let
 * a screen pass that confirmed a choice nobody was shown held.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatChoosesQuality implements ChoosingQuality
{
    private ?APresetToChoose $chosen = null;

    private ?APresetToChoose $confirmed = null;

    private int $readings = 0;

    private int $choices = 0;

    private int $confirmations = 0;

    private function __construct(
        private readonly WhatWasFoundOfTheQuality $reading,
        private readonly WhatTheChoiceCameTo $answer,
    ) {}

    /** A stack with this in force, which answers every choice with what the test gives. */
    public static function with(TheQualityChosen $inForce, ?WhatTheChoiceCameTo $answer = null): self
    {
        return new self(WhatWasFoundOfTheQuality::found($inForce), $answer ?? WhatTheChoiceCameTo::inForce($inForce));
    }

    /** A stack the operator could not reach, for the reason given, whatever is asked. */
    public static function met(Obstacle $why): self
    {
        return new self(WhatWasFoundOfTheQuality::met($why), WhatTheChoiceCameTo::met($why));
    }

    /** The choice last asked for, or nothing where none was. */
    public function chosen(): ?APresetToChoose
    {
        return $this->chosen;
    }

    /** The held choice last confirmed, or nothing where none was. */
    public function confirmed(): ?APresetToChoose
    {
        return $this->confirmed;
    }

    /** How many times what is in force was read. */
    public function readings(): int
    {
        return $this->readings;
    }

    /** How many choices were asked for, confirmations aside. */
    public function choices(): int
    {
        return $this->choices;
    }

    /** How many held choices were confirmed. */
    public function confirmations(): int
    {
        return $this->confirmations;
    }

    #[Override]
    public function inForceOn(Stack $stack, Session $session): WhatWasFoundOfTheQuality
    {
        $this->readings++;

        return $this->reading;
    }

    #[Override]
    public function choose(Stack $stack, Session $session, APresetToChoose $asked): WhatTheChoiceCameTo
    {
        $this->chosen = $asked;
        $this->choices++;

        return $this->answer;
    }

    #[Override]
    public function confirm(Stack $stack, Session $session, AHeldChoice $agreed): WhatTheChoiceCameTo
    {
        $this->confirmed = $agreed->asked();
        $this->confirmations++;

        return $this->answer;
    }
}
