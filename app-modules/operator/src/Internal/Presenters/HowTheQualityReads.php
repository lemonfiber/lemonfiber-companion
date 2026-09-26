<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AFormatChoiceMade;
use Modules\Kernel\Api\AFormatInForce;
use Modules\Kernel\Api\APresetInForce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheQualityChosen;
use Modules\Kernel\Api\WhatBecameOfTheChoice;
use Modules\Operator\Internal\ViewModels\AFormatAsShown;
use Modules\Operator\Internal\ViewModels\AFormatChoiceAsShown;
use Modules\Operator\Internal\ViewModels\APresetAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheQualityTurnedOutToBe;

/**
 * What asking a stack about quality produces, as the fields a screen draws.
 *
 * `F2`: data in, view model out. Every word is handed on as the stack wrote
 * it; nothing here names a preset or works out a size.
 *
 * **Why a choice was held is what the stack said of the presets it held.**
 * The stack holds a choice because this machine would transcode it in
 * software, and says so per preset with `needs_transcoding_here` and what
 * playing it costs. Those sentences are the reason, and they are carried only
 * on a held answer: on any other, a preset that transcodes here is a caution
 * on its own row rather than a reason for anything.
 */
final readonly class HowTheQualityReads
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): TheQualityTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::theSessionEnded());
    }

    /** The stack answered, and this is what is in force. */
    public function this(TheQualityChosen $chosen): TheQualityTurnedOutToBe
    {
        $presets = [];
        $held = [];

        foreach ($chosen->presets() as $preset) {
            $presets[] = $this->preset($preset);

            if ($preset->transcodesHere()) {
                $held[] = $preset->transcoding();
            }
        }

        return new TheQualityTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            presets: $presets,
            music: $chosen->music()->either(set: $this->format(...), unset: AFormatAsShown::none(...)),
            becameSaid: $chosen->became()->saidOnTheScreen(),
            heldBecause: $chosen->became() === WhatBecameOfTheChoice::Held ? $held : [],
            customised: $chosen->customised(),
        );
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): TheQualityTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::somethingStopped($why));
    }

    /** What choosing a format for music did. */
    public function music(AFormatChoiceMade $made): AFormatChoiceAsShown
    {
        return new AFormatChoiceAsShown(
            format: $this->format($made->format()),
            becameSaid: $made->became()->saidOnTheScreen(),
            appliedSaid: $made->applied()->saidOnTheScreen(),
            detail: $made->applied()->detail(),
        );
    }

    /** One preset in force, as the row that draws it. */
    private function preset(APresetInForce $preset): APresetAsShown
    {
        return new APresetAsShown(
            scope: $preset->scope(),
            preset: $preset->preset(),
            means: $preset->means(),
            resolution: $preset->resolution(),
            sizePerHour: $preset->sizePerHour(),
            transcoding: $preset->transcoding(),
            transcodesHere: $preset->transcodesHere(),
        );
    }

    /** The format chosen for music, as the rows that draw it. */
    private function format(AFormatInForce $format): AFormatAsShown
    {
        return new AFormatAsShown(
            scope: $format->scope(),
            format: $format->format(),
            means: $format->means(),
            targets: $format->targets(),
            sizePerHour: $format->sizePerHour(),
            note: $format->note(),
        );
    }

    /** An answer with nothing in it, for a reading that did not come back. */
    private function nothingFrom(HowTheReadingWent $went): TheQualityTurnedOutToBe
    {
        return new TheQualityTurnedOutToBe(
            went: $went,
            presets: [],
            music: AFormatAsShown::none(),
            becameSaid: '',
            heldBecause: [],
            customised: false,
        );
    }
}
