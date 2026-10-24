<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One plugin's settings and secrets, flattened for a template. */
final readonly class WhatAPluginsSettingsShow
{
    /**
     * @param HowTheReadingWent     $went        whether both readings came back, or what stood in the way
     * @param bool                  $isInstalled whether the plugin is among those installed
     * @param string                $plugin      what the plugin calls itself
     * @param list<APluginsSettingAsShown> $settings    every setting its services read
     * @param list<ASecretAsShown>  $secrets     every secret its services read
     * @param list<AGrantAsShown>   $grants      every credential of another service granted to it
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $isInstalled,
        public string $plugin,
        public array $settings,
        public array $secrets,
        public array $grants,
    ) {}
}
