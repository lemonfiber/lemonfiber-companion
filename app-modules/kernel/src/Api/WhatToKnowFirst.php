<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What an operator should know about a removal before agreeing to it.
 *
 * What beneath the data location is not lemonfiber's, what is still coming
 * down, what lemonfiber cannot take, and the stack's own sentences about the
 * data location being on a network share or a drive that unplugs and about the
 * copy it takes first. Each sentence is empty where the stack said none.
 */
final readonly class WhatToKnowFirst
{
    private function __construct(
        private WhatIsNotLemonfibers $foreign,
        private WhatIsStillComing $coming,
        private WhatItCannotTake $outside,
        private string $volume,
        private string $copyFirst,
    ) {}

    /** These, as the stack said them; a sentence it did not say is empty. */
    public static function said(
        WhatIsNotLemonfibers $foreign,
        WhatIsStillComing $coming,
        WhatItCannotTake $outside,
        string $volume = '',
        string $copyFirst = '',
    ): self {
        return new self($foreign, $coming, $outside, $volume, $copyFirst);
    }

    /** What beneath the data location is not lemonfiber's. */
    public function foreign(): WhatIsNotLemonfibers
    {
        return $this->foreign;
    }

    /** What is still coming down, which stopping would interrupt. */
    public function coming(): WhatIsStillComing
    {
        return $this->coming;
    }

    /** What lemonfiber cannot take, each with how to take it by hand. */
    public function outside(): WhatItCannotTake
    {
        return $this->outside;
    }

    /** The stack's sentence about the data location being on a network share or a drive that unplugs, or nothing where it said none. */
    public function volume(): string
    {
        return $this->volume;
    }

    /** The stack's sentence about the copy it takes before configuration goes, or nothing where it said none. */
    public function copyFirst(): string
    {
        return $this->copyFirst;
    }
}
