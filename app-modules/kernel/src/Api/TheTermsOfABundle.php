<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How a support bundle was made, as the bundle itself states it.
 *
 * Read from the bundle rather than from what was asked, so what is shown is
 * what whoever receives it will read — the settings it reveals above all.
 */
final readonly class TheTermsOfABundle
{
    private function __construct(
        private string $window,
        private WhatFilenamesShow $filenames,
        private SettingsToReveal $revealed,
    ) {}

    /** The terms a bundle carries. */
    public static function stated(string $window, WhatFilenamesShow $filenames, SettingsToReveal $revealed): self
    {
        return new self($window, $filenames, $revealed);
    }

    /** How much of each service's logs it takes, in the stack's words. */
    public function window(): string
    {
        return $this->window;
    }

    /** Whether media filenames are shown or replaced. */
    public function filenames(): WhatFilenamesShow
    {
        return $this->filenames;
    }

    /** The settings it shows as they are. */
    public function revealed(): SettingsToReveal
    {
        return $this->revealed;
    }
}
