<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ASettingToReveal;
use Modules\Kernel\Api\SettingsToReveal;
use Tests\Support\WhatABundleSays;

it('reveals nothing until a setting is added', function (): void {
    expect(WhatABundleSays::namesOf(SettingsToReveal::none()))->toBe([])
        ->and(SettingsToReveal::none()->count())->toBe(0);
});

it('adds one setting at a time, in the order they were agreed, and holds each once', function (): void {
    $revealed = SettingsToReveal::none()
        ->with(ASettingToReveal::named('SONARR_URL'))
        ->with(ASettingToReveal::named('RADARR_URL'))
        ->with(ASettingToReveal::named('SONARR_URL'));

    expect(WhatABundleSays::namesOf($revealed))->toBe(['SONARR_URL', 'RADARR_URL'])
        ->and($revealed->count())->toBe(2)
        ->and($revealed->holds(ASettingToReveal::named('RADARR_URL')))->toBeTrue()
        ->and($revealed->holds(ASettingToReveal::named('LIDARR_URL')))->toBeFalse();
});

it('takes back one setting and keeps the rest in their order', function (): void {
    $revealed = SettingsToReveal::none()
        ->with(ASettingToReveal::named('SONARR_URL'))
        ->with(ASettingToReveal::named('RADARR_URL'))
        ->with(ASettingToReveal::named('LIDARR_URL'))
        ->without(ASettingToReveal::named('RADARR_URL'));

    expect(WhatABundleSays::namesOf($revealed))->toBe(['SONARR_URL', 'LIDARR_URL'])
        ->and(WhatABundleSays::namesOf($revealed->without(ASettingToReveal::named('BAZARR_URL'))))->toBe(['SONARR_URL', 'LIDARR_URL']);
});
