<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A choice the stack held rather than recorded, which the operator may confirm.
 *
 * {@see ChoosingQuality::confirm()} takes one of these and nothing else, and
 * the only way to make one is against the answer that held the choice. Seeing
 * a held choice builds nothing a port would carry out: the yes is a tap on
 * what this names, and asking the stack about any other choice cannot reach
 * it.
 */
final readonly class AHeldChoice
{
    private function __construct(private APresetToChoose $asked) {}

    /** The choice asked for, held in the answer given; an answer that held nothing is refused. */
    public static function of(APresetToChoose $asked, TheQualityChosen $answered): self
    {
        if ($answered->became() !== WhatBecameOfTheChoice::Held) {
            throw ThereIsNothingToAgreeTo::held($answered->became());
        }

        return new self($asked);
    }

    /** The choice that was held, to be asked for again with the yes. */
    public function asked(): APresetToChoose
    {
        return $this->asked;
    }
}
