<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ACredentialGrant;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ARecipe;
use Modules\Kernel\Api\AValueItCarries;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheCredentialGrants;
use Modules\Kernel\Api\TheInputsARecipeAsks;
use Modules\Kernel\Api\TheRecipes;
use Modules\Kernel\Api\TheRecipeSteps;
use Modules\Kernel\Api\TheValuesItCarries;
use Modules\Kernel\Api\WhatVouchesForAPlugin;
use Modules\Kernel\Api\WhenARecipeRuns;
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

/** A recipe that runs then, each value it carries asking for one of these approvals. */
function aRecipeAsking(string $id, WhenARecipeRuns $runs, string ...$approvals): ARecipe
{
    return ARecipe::of(
        $id,
        'A recipe',
        '',
        TheRecipeSteps::these(),
        TheValuesItCarries::these(...array_map(static fn(string $approval): AValueItCarries => AValueItCarries::listed('key', 'stack-service', 'sonarr', $approval, '', ''), $approvals)),
        $runs,
        TheInputsARecipeAsks::these(),
    );
}

it('asks once for each approval an install recipe needs, past any recipe run on demand, whatever the stack calls it', function (): void {
    $plugin = APlugin::named('tdarr', '2.1.0', 'Tdarr', WhatVouchesForAPlugin::recorded('', '', '', reviewed: false, upstream: '', licence: ''), TheRecipes::these(
        aRecipeAsking('first', WhenARecipeRuns::Install, 'field', 'plex.token@tdarr'),
        aRecipeAsking('asked', WhenARecipeRuns::Demand, 'asked@tdarr'),
        aRecipeAsking('last', WhenARecipeRuns::Install, 'plex.token@tdarr', 'sonarr.key@tdarr'),
    ));

    expect(iterator_to_array($plugin->approvals(), preserve_keys: false))->toBe(['field', 'plex.token@tdarr', 'sonarr.key@tdarr'])
        ->and(iterator_to_array(aRecipeAsking('one', WhenARecipeRuns::Demand, 'field', 'field')->approvals(), preserve_keys: false))->toBe(['field']);
});
