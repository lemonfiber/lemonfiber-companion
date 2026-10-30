<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheInstalledPlugins;
use Modules\Kernel\Api\TheRecipes;
use Modules\Kernel\Api\WhatVouchesForAPlugin;

/** A plugin installed with these services. */
function aPluginRunning(string $id, string ...$services): APlugin
{
    return APlugin::named($id, '1.0.0', $id, WhatVouchesForAPlugin::recorded($id, '', '', reviewed: false, upstream: '', licence: ''), TheRecipes::these(), ...array_map(ServiceId::called(...), $services));
}

it('names every service of every plugin installed', function (): void {
    $services = TheInstalledPlugins::these(aPluginRunning('gluetun', 'gluetun'), aPluginRunning('arr-pack', 'sonarr', 'radarr'))->services();

    expect(array_map(static fn(ServiceId $service): string => $service->named(), iterator_to_array($services, preserve_keys: false)))->toBe(['gluetun', 'sonarr', 'radarr']);
});

it('names no service where no plugin is installed', function (): void {
    expect(TheInstalledPlugins::these()->services()->isEmpty())->toBeTrue();
});
