<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * How long a verb takes something away for, flattened to what a line draws.
 *
 * The template hands both to `__()` and the key decides whether the number is
 * used.
 */
final readonly class WhatAVerbTakesAwaySays
{
    /**
     * @param string   $said    the catalogue key for the shape this arrived in
     * @param int|null $seconds how long for, where the shape has a length at all
     */
    public function __construct(
        public string $said,
        public ?int $seconds,
    ) {}
}
