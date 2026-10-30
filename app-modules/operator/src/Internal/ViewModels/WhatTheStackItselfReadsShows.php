<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** The settings and secrets no installed plugin reads, grouped by whose they are, flattened for a template. */
final readonly class WhatTheStackItselfReadsShows
{
    /**
     * @param HowTheReadingWent             $went   whether both readings came back, or what stood in the way
     * @param list<AnOwnersSettingsAsShown> $owners each owner's, the core's first and then each namespace's in the order the stack lists them
     * @param bool                          $waits  whether the settings wait for a frame of their own, the plugins having been read on this one
     */
    public function __construct(public HowTheReadingWent $went, public array $owners = [], public bool $waits = false) {}
}
