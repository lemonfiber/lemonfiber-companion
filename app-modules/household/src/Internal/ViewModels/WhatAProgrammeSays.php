<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * A later programme in the guide, as its sheet tells it: its channel, title, when it starts and ends, and its certificate.
 *
 * Nothing to press: the guide neither tunes ahead nor reminds.
 */
final readonly class WhatAProgrammeSays
{
    /**
     * @param string $channel     the channel it is on, as its row names it
     * @param string $times       when it starts and ends, as a clock reads them
     * @param string $certificate its certificate, or empty where it carries none
     */
    public function __construct(public string $channel, public string $title, public string $times, public string $certificate) {}
}
