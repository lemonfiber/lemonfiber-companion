<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One row of the settings listing, as the screen draws it.
 *
 * **`said` is what to print and `withheld` is how to print it, and the two are
 * not the same question.** A withheld row still has something to show — the
 * stack's own note that the value is set — so the template is not choosing
 * between text and nothing. It is choosing between a value and a note about a
 * value, which want different weight on the screen and, one day, a different
 * control beside them.
 */
final readonly class WhatOneSettingSays
{
    public function __construct(
        public string $key,
        public string $said,
        public bool $withheld,
    ) {}
}
