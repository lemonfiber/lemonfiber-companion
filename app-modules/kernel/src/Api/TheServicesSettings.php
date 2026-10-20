<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Every setting and secret a stack's services read.
 *
 * None at all is an answer, apart from *could not be read*, which is an
 * {@see Obstacle} that {@see WhatWasFoundOfTheSettings} keeps apart.
 */
final readonly class TheServicesSettings
{
    private function __construct(private TheSettingsRead $settings, private TheSecretsRead $secrets) {}

    /** These settings and secrets. */
    public static function of(TheSettingsRead $settings, TheSecretsRead $secrets): self
    {
        return new self($settings, $secrets);
    }

    /** Only the settings and secrets one of these services reads. */
    public function readByAnyOf(Services $services): self
    {
        return new self($this->settings->readByAnyOf($services), $this->secrets->readByAnyOf($services));
    }

    /** Every setting, in name order. */
    public function settings(): TheSettingsRead
    {
        return $this->settings;
    }

    /** Every secret, in name order, never with its value. */
    public function secrets(): TheSecretsRead
    {
        return $this->secrets;
    }
}
