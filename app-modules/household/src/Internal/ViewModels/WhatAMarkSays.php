<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * One half hour along the top of the guide: the time a clock reads at its start, and how wide it is drawn.
 *
 * Plain text and a width, so the screen's state can hold it.
 */
final readonly class WhatAMarkSays
{
    /**
     * @param string $time  the time it starts at, as a clock reads it to the minute
     * @param int    $width how wide it is drawn, in points
     */
    public function __construct(public string $time, public int $width) {}
}
