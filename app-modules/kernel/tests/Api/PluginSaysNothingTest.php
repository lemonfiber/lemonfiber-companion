<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use Closure;

use function expect;
use function it;

use Modules\Kernel\Api\ACapabilityLeftContested;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginChange;
use Modules\Kernel\Api\APluginSource;
use Modules\Kernel\Api\AProof;
use Modules\Kernel\Api\ARecipe;
use Modules\Kernel\Api\ARecipeStep;
use Modules\Kernel\Api\ASettingItOverrides;
use Modules\Kernel\Api\ASourceAsked;
use Modules\Kernel\Api\AValueItCarries;
use Modules\Kernel\Api\HowItsSourceStands;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\PluginSaysNothing;
use Modules\Kernel\Api\TheRecipes;
use Modules\Kernel\Api\TheRecipeSteps;
use Modules\Kernel\Api\TheValuesItCarries;
use Modules\Kernel\Api\WhatAChangePuts;
use Modules\Kernel\Api\WhatAProofCameTo;
use Modules\Kernel\Api\WhatVouchesForAPlugin;

use function sprintf;

/**
 * Every word about a plugin that may not arrive blank, with the field each refusal names.
 *
 * @return array<string, array{Closure(): object, string}>
 */
function everyWordAPluginOwes(): array
{
    $vouched = WhatVouchesForAPlugin::recorded('', '', '', reviewed: false, upstream: '', licence: '');

    return [
        'a source' => [static fn(): object => APluginSource::typed('  '), 'source'],
        'a plugin id' => [static fn(): object => APlugin::named(' ', '1', '', $vouched, TheRecipes::these()), 'plugin'],
        'a version' => [static fn(): object => APlugin::named('tdarr', '', '', $vouched, TheRecipes::these()), 'version'],
        'a recipe id' => [static fn(): object => ARecipe::of('', 'Title', '', TheRecipeSteps::these(), TheValuesItCarries::these()), 'id'],
        'a recipe title' => [static fn(): object => ARecipe::of('link', ' ', '', TheRecipeSteps::these(), TheValuesItCarries::these()), 'title'],
        'a step method' => [static fn(): object => ARecipeStep::calling('ask', '', 'sonarr', '/a', ''), 'method'],
        'a step destination' => [static fn(): object => ARecipeStep::calling('ask', 'GET', ' ', '/a', ''), 'to'],
        'a value' => [static fn(): object => AValueItCarries::listed('', '', 'tdarr', '', '', ''), 'value'],
        'where a value goes' => [static fn(): object => AValueItCarries::listed('key', '', '', '', '', ''), 'to'],
        'a change path' => [static fn(): object => APluginChange::at(' ', WhatAChangePuts::Region), 'path'],
        'a proof id' => [static fn(): object => AProof::of('', 'It answers', 'GET /', '', WhatAProofCameTo::notAsked()), 'proof'],
        'what a proof asks' => [static fn(): object => AProof::of('answers', 'It answers', ' ', '', WhatAProofCameTo::notAsked()), 'asks'],
        'a contested capability' => [static fn(): object => ACapabilityLeftContested::over(' ', 'sonarr', PluginLines::none()), 'capability'],
        'an overridden setting' => [static fn(): object => ASettingItOverrides::of('', 'why'), 'setting'],
        'a source\'s plugin' => [static fn(): object => ASourceAsked::of(' ', HowItsSourceStands::reachable()), 'plugin'],
        'why a source is unreachable' => [static fn(): object => HowItsSourceStands::unreachable(' '), 'why'],
        'why a source was not asked' => [static fn(): object => HowItsSourceStands::unasked(''), 'why'],
        'why a proof is unproven' => [static fn(): object => WhatAProofCameTo::unproven(' '), 'why'],
        'a line' => [static fn(): object => PluginLines::under('faults', 'It answered 502', ' '), 'faults'],
    ];
}

it('refuses every word a plugin owes arriving blank, naming the word', function (): void {
    foreach (everyWordAPluginOwes() as $which => [$make, $field]) {
        expect($make)->toThrow(PluginSaysNothing::class, sprintf('`%s`', $field), $which);
    }
});

it('keeps what it was given, trimmed where it is said to be', function (): void {
    $vouched = WhatVouchesForAPlugin::recorded(' tdarr ', ' abc ', ' key ', reviewed: true, upstream: ' https://example.com ', licence: ' MIT ');
    $plugin = APlugin::named('tdarr', '2.1.0', '  ', $vouched, TheRecipes::these());

    expect(APluginSource::typed('  tdarr@v2  ')->said())->toBe('tdarr@v2')
        ->and($plugin->shown())->toBe('tdarr')
        ->and($vouched->source())->toBe('tdarr')
        ->and($vouched->revision())->toBe('abc')
        ->and($vouched->signed())->toBe('key')
        ->and($vouched->upstream())->toBe('https://example.com')
        ->and($vouched->licence())->toBe('MIT')
        ->and(ASettingItOverrides::of('sonarr.rename', ' because ')->why())->toBe('because')
        ->and(ACapabilityLeftContested::over('transcode', ' ', PluginLines::none())->by())->toBe('');
});
