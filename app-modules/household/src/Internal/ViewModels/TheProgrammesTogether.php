<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * Programmes too short to press one by one, as the list a cell drawn for them opens: their channel, the time they run across, and each of them.
 */
final readonly class TheProgrammesTogether
{
    /**
     * @param string              $channel    the channel they are on, as its row names it
     * @param string              $times      from the first one's start to the last one's end, as a clock reads them
     * @param list<WhatACellSays> $programmes each of them, in the order they run, pressed as its own cell would be
     */
    public function __construct(public string $channel, public string $times, public array $programmes) {}
}
