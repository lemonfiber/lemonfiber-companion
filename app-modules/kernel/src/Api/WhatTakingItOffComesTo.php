<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What one removal would take off a machine, or took, as the stack read it.
 *
 * Which removal, what it takes and what it leaves in the operator's words,
 * every line it reaches, what those that go occupy, what beneath the data
 * location is not lemonfiber's, what is still coming down, what lemonfiber
 * cannot take, how much of it was read, and the name the reading goes by.
 *
 * **The agreement is this reading's name**, so a yes quoting it is a yes to
 * exactly this; a reading that has moved on since is refused by the stack.
 * Where the data location is on a network share or a drive that unplugs, and
 * where a copy is taken first, the stack's own sentence is carried, and
 * nothing is where it said nothing.
 */
final readonly class WhatTakingItOffComesTo
{
    private function __construct(
        private WhichRemoval $tier,
        private WhatGoesAndWhatStays $words,
        private WhatItReaches $items,
        private int $bytes,
        private WhatToKnowFirst $first,
        private HowMuchWasRead $confidence,
        private string $agreement,
    ) {}

    /** The stack's reading of one removal; a blank agreement, or a figure below none, is refused. */
    public static function read(
        WhichRemoval $tier,
        WhatGoesAndWhatStays $words,
        WhatItReaches $items,
        int $bytes,
        WhatToKnowFirst $first,
        HowMuchWasRead $confidence,
        string $agreement,
    ): self {
        if (trim($agreement) === '') {
            throw UninstallSaysNothing::about('agreement');
        }

        if ($bytes < 0) {
            throw UninstallSaysNothing::outside('bytes', $bytes);
        }

        return new self($tier, $words, $items, $bytes, $first, $confidence, $agreement);
    }

    /** Which removal this is. */
    public function tier(): WhichRemoval
    {
        return $this->tier;
    }

    /** What it takes, in the operator's words. */
    public function removes(): string
    {
        return $this->words->removes();
    }

    /** What it leaves alone, in the operator's words. */
    public function keeps(): string
    {
        return $this->words->keeps();
    }

    /** Every line it reaches, going and kept. */
    public function items(): WhatItReaches
    {
        return $this->items;
    }

    /** What the lines that go occupy, where that is knowable; what is kept is not counted. */
    public function bytes(): int
    {
        return $this->bytes;
    }

    /** What beneath the data location is not lemonfiber's. */
    public function foreign(): WhatIsNotLemonfibers
    {
        return $this->first->foreign();
    }

    /** What is still coming down, which stopping would interrupt. */
    public function coming(): WhatIsStillComing
    {
        return $this->first->coming();
    }

    /** What lemonfiber cannot take, each with how to take it by hand. */
    public function outside(): WhatItCannotTake
    {
        return $this->first->outside();
    }

    /** How much of this was read, and what could not be. */
    public function confidence(): HowMuchWasRead
    {
        return $this->confidence;
    }

    /** What this reading is called, so a yes can name it. */
    public function agreement(): string
    {
        return $this->agreement;
    }

    /** The stack's sentence about the data location being on a network share or a drive that unplugs, or nothing where it said none. */
    public function volume(): string
    {
        return $this->first->volume();
    }

    /** The stack's sentence about the copy it takes before configuration goes, or nothing where it said none. */
    public function copyFirst(): string
    {
        return $this->first->copyFirst();
    }
}
