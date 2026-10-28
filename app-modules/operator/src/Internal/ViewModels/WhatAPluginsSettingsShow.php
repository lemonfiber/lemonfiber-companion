<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One plugin's settings and secrets, flattened for a template. */
final readonly class WhatAPluginsSettingsShow
{
    /**
     * @param HowTheReadingWent             $went whether both readings came back, or what stood in the way
     * @param ThePluginsSettingsAsRead|null $read what the plugin's services read, or null where they did not come back or the plugin is not installed
     */
    public function __construct(public HowTheReadingWent $went, public ?ThePluginsSettingsAsRead $read = null) {}
}
