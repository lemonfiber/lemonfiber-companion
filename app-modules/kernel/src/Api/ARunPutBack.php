<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What putting a run back came to, as the stack reported it.
 *
 * Three lists and a flag. What went back; what was **left**, each with why,
 * which is the list worth reading when it is not empty; and what going back
 * means beyond the changes themselves. The flag says whether any of it
 * happened: a rehearsal names the same changes as what *would* go back and
 * what it cannot promise.
 *
 * **Complete only where nothing was left.** {@see self::leftNothing()} is the
 * one place that is decided, so a partial reversal cannot be drawn as a whole
 * one by a screen that forgot to look at the second list.
 */
final readonly class ARunPutBack
{
    private function __construct(
        private WhetherItWasRehearsed $rehearsed,
        private WhatWentBack $reversed,
        private ChangesAndWhy $left,
        private ChangesAndWhy $noted,
    ) {}

    /** The report as the stack gave it. */
    public static function reported(
        WhetherItWasRehearsed $rehearsed,
        WhatWentBack $reversed,
        ChangesAndWhy $left,
        ChangesAndWhy $noted,
    ): self {
        return new self($rehearsed, $reversed, $left, $noted);
    }

    /** Whether this only said what would go back, or put it back. */
    public function rehearsed(): WhetherItWasRehearsed
    {
        return $this->rehearsed;
    }

    /** What went back, or would. */
    public function reversed(): WhatWentBack
    {
        return $this->reversed;
    }

    /** What did not go back, or could not be promised, each with why. */
    public function left(): ChangesAndWhy
    {
        return $this->left;
    }

    /** What going back means beyond the changes themselves. */
    public function noted(): ChangesAndWhy
    {
        return $this->noted;
    }

    /** Whether everything went back, which is only where nothing was left. */
    public function leftNothing(): bool
    {
        return $this->left->count() === 0;
    }
}
