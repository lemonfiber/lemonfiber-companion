<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\WhoSetIt;

/**
 * One row of the settings listing, as the screen draws it.
 *
 * **`said` is what to print and `withheld` is how to print it, and the two are
 * not the same question.** A withheld row still has something to show — the
 * stack's own note that the value is set — so the template is not choosing
 * between text and nothing. It is choosing between a value and a note about a
 * value, which want different weight on the screen and, one day, a different
 * control beside them.
 *
 * **`came` is a key and `attributed` is what fills it, which is where the
 * words belong.** The presenter chose the arm; the template says them. Two of
 * the four arms have nothing to fill it with and carry `null` rather than an
 * empty string — the template has no placeholder to put a blank in on those
 * rows, so a blank there is a value nothing could read and nothing could tell
 * from any other. Two of the four arms
 * have something to interpolate — the plugin's name, and the stack's reason an
 * origin is unknown — and one string covers both because no row is ever both.
 * A presenter reaching for `__()` would be a class translating without having
 * asked for a translator, which is the thing `L1` is about.
 */
final readonly class WhatOneSettingSays
{
    public function __construct(
        public string $key,
        public string $said,
        public bool $withheld,
        public bool $mayBeChanged,
        public WhoSetIt $came,
        public ?string $attributed = null,
    ) {}
}
