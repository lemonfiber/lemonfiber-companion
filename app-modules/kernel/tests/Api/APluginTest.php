<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACredentialGrant;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheCredentialGrants;
use Modules\Kernel\Api\TheRecipes;
use Modules\Kernel\Api\WhatVouchesForAPlugin;
use Tests\Support\APluginAsItArrives;

it('offers an update only where its record names the source it came from', function (): void {
    expect(APluginAsItArrives::held()->canBeUpdated())->toBeTrue()
        ->and(APlugin::named('tdarr', '2.1.0', 'Tdarr', WhatVouchesForAPlugin::recorded('', '', '', reviewed: false, upstream: '', licence: ''), TheRecipes::these())->canBeUpdated())->toBeFalse();
});

it('holds the grants its record keeps, and none until it is given them', function (): void {
    $plugin = APluginAsItArrives::held();
    $grants = TheCredentialGrants::of(ACredentialGrant::of(APluginId::called('tdarr'), 'plex.token', ServiceId::called('plex'), 'library-scan', AMomentAsWritten::inSeconds('')));
    $holding = $plugin->holding($grants);

    expect($plugin->grants())->toHaveCount(0)
        ->and($holding->grants())->toBe($grants)
        ->and([$holding->id(), $holding->version(), $holding->shown(), $holding->recipes(), $holding->services(), $holding->vouched()])
        ->toBe([$plugin->id(), $plugin->version(), $plugin->shown(), $plugin->recipes(), $plugin->services(), $plugin->vouched()]);
});
