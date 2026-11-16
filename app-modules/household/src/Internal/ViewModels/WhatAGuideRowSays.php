<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * One channel's row in the guide: its name and number, which tune it when pressed, and its programmes along the hours.
 *
 * Plain text, widths and taps, so the screen's state can hold it.
 */
final readonly class WhatAGuideRowSays
{
    /**
     * @param string             $id     the channel, by the id the core lists it under
     * @param string             $number its number on the tuner, or empty
     * @param string             $shown  its number and name together, as a screen reader says it
     * @param string             $tap    what pressing the channel does
     * @param list<WhatACellSays> $cells  its programmes and gaps, left to right, from the guide's first half hour
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $number,
        public string $shown,
        public string $tap,
        public array $cells,
    ) {}
}
