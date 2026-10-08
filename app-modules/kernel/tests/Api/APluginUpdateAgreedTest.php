<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginActWasNotRehearsed;
use Modules\Kernel\Api\APluginUpdateAgreed;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\TheRecipes;
use Modules\Kernel\Api\WhatVouchesForAPlugin;
use Tests\Support\APluginAsItArrives;

/** Another plugin than the one every reading here is about. */
function aPluginNoReadingIsAbout(): APlugin
{
    return APlugin::named('unmanic', '0.2.0', 'Unmanic', WhatVouchesForAPlugin::recorded('unmanic', '', '', reviewed: false, upstream: '', licence: ''), TheRecipes::these());
}

it('agrees to an update only against a reading of updating that plugin', function (): void {
    $tdarr = APluginAsItArrives::held();

    foreach ([
        'the listing' => [APluginAsItArrives::theListing(), $tdarr],
        'what an update came to' => [APluginAsItArrives::theUpdateNotHeld(), $tdarr],
        'a reading of removing it' => [APluginAsItArrives::theRemovalReading(), $tdarr],
        'a reading of installing' => [APluginAsItArrives::theReading(), $tdarr],
        'another plugin' => [APluginAsItArrives::theUpdateReading(), aPluginNoReadingIsAbout()],
    ] as $which => [$reading, $plugin]) {
        expect(static fn(): APluginUpdateAgreed => APluginUpdateAgreed::after($reading, $plugin, PluginLines::none()))
            ->toThrow(APluginActWasNotRehearsed::class, 'Agreed to update a plugin', $which);
    }

    $agreed = APluginUpdateAgreed::after(APluginAsItArrives::theUpdateReading(), $tdarr, PluginLines::under('approved', 'secrets@elsewhere', APluginAsItArrives::APPROVAL));
    $approved = [];

    foreach ($agreed->approved() as $value) {
        $approved[] = $value;
    }

    expect($agreed->plugin())->toBe('tdarr')
        ->and($agreed->source()->said())->toBe('tdarr')
        ->and($agreed->agreement())->toBe(APluginAsItArrives::AGREEMENT)
        ->and($approved)->toBe([APluginAsItArrives::APPROVAL]);
});
