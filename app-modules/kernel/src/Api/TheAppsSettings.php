<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * This app's own page in the phone's settings, opened for the operator.
 *
 * Where a permission this app was refused is granted again: a remedy that says
 * *allow it in Settings* is one tap from being done rather than a hunt through
 * the phone's menus. The page belongs to the platform, so all that comes back
 * is whether it opened; whether the operator changed anything there is read
 * the next time the app reaches what the permission guards.
 */
interface TheAppsSettings
{
    /** Open this app's page in the phone's settings, and say whether it opened. */
    public function open(): WhetherTheSettingsOpened;
}
