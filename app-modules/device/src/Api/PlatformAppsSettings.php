<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Lemonfiber\Native\AppsSettings;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhetherTheSettingsOpened;

/**
 * This app's page in the phone's settings, asked for through lemonfiber's own call.
 *
 * A platform call sits behind an adapter, and this is the whole of this one:
 * the bridge answers whether the page opened, and this says it in the kernel's
 * terms. What counts as opened is {@see AppsSettings}' to decide.
 */
final readonly class PlatformAppsSettings implements TheAppsSettings
{
    public function __construct(private AppsSettings $bridge) {}

    public function open(): WhetherTheSettingsOpened
    {
        return $this->bridge->open() ? WhetherTheSettingsOpened::opened() : WhetherTheSettingsOpened::wouldNot();
    }
}
