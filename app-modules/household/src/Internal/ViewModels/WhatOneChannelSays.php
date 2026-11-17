<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * One channel on Home's Live TV shelf, as it is drawn: its name and number, and what is on it now until when.
 *
 * Plain text and a tap, so the screen's state can hold it.
 */
final readonly class WhatOneChannelSays
{
    /**
     * @param string $id        the channel, by the id the core lists it under
     * @param string $number    its number on the tuner, or empty
     * @param string $programme what is on it now, or empty where the guide says nothing
     * @param string $until     when that ends, as a clock reads it, or empty where the guide does not say
     * @param string $tap       what pressing it does
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $number,
        public string $programme,
        public string $until,
        public string $certificate,
        public string $tap,
    ) {}
}
