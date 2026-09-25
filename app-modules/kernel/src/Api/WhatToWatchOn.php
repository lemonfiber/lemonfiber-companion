<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Which app to watch on, device by device, and what to do when it does not work.
 *
 * `onlyAtHome` is said once because it is true of every device: nothing here
 * reaches the stack from outside the household network.
 */
final readonly class WhatToWatchOn
{
    private function __construct(
        private TheDevices $devices,
        private string $onlyAtHome,
        private string $nothingIsInstalled,
        private WhyPlaybackMayStruggle $straining,
        private TheTroubles $troubles,
    ) {}

    /** What the stack advised; both sentences are required. */
    public static function advised(
        TheDevices $devices,
        string $onlyAtHome,
        string $nothingIsInstalled,
        WhyPlaybackMayStruggle $straining,
        TheTroubles $troubles,
    ): self {
        foreach (['only_at_home' => $onlyAtHome, 'nothing_is_installed' => $nothingIsInstalled] as $field => $said) {
            if (trim($said) === '') {
                throw AdviceSaysNothing::about($field);
            }
        }

        return new self($devices, $onlyAtHome, $nothingIsInstalled, $straining, $troubles);
    }

    /** Every device, in the order somebody is likely to be holding one. */
    public function devices(): TheDevices
    {
        return $this->devices;
    }

    /** Where every device works, said once. */
    public function onlyAtHome(): string
    {
        return $this->onlyAtHome;
    }

    /** What this will not do for them. */
    public function nothingIsInstalled(): string
    {
        return $this->nothingIsInstalled;
    }

    /** Why playback here may struggle before any app is chosen, or nothing. */
    public function straining(): WhyPlaybackMayStruggle
    {
        return $this->straining;
    }

    /** What to do when it does not work, keyed by the symptom. */
    public function troubles(): TheTroubles
    {
        return $this->troubles;
    }
}
