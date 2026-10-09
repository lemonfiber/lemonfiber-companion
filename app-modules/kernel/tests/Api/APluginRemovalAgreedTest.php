<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginActWasNotRehearsed;
use Modules\Kernel\Api\APluginRemovalAgreed;
use Modules\Kernel\Api\TheRecipes;
use Modules\Kernel\Api\WhatVouchesForAPlugin;
use Tests\Support\APluginAsItArrives;

/** Another plugin than the one every reading here is about. */
function aPluginNoRemovalIsAbout(): APlugin
{
    return APlugin::named('unmanic', '0.2.0', 'Unmanic', WhatVouchesForAPlugin::recorded('unmanic', '', '', reviewed: false, upstream: '', licence: ''), TheRecipes::these());
}

it('agrees to a removal only against a reading of removing that plugin', function (): void {
    $tdarr = APluginAsItArrives::held();

    foreach ([
        'the listing' => [APluginAsItArrives::theListing(), $tdarr],
        'what a removal came to' => [APluginAsItArrives::thePartialRemoval(), $tdarr],
        'a reading of updating it' => [APluginAsItArrives::theUpdateReading(), $tdarr],
        'another plugin' => [APluginAsItArrives::theRemovalReading(), aPluginNoRemovalIsAbout()],
    ] as $which => [$reading, $plugin]) {
        expect(static fn(): APluginRemovalAgreed => APluginRemovalAgreed::after($reading, $plugin))
            ->toThrow(APluginActWasNotRehearsed::class, 'Agreed to remove a plugin', $which);
    }

    $agreed = APluginRemovalAgreed::after(APluginAsItArrives::theRemovalReading(), $tdarr);

    expect($agreed->plugin())->toBe('tdarr')
        ->and($agreed->agreement())->toBe(APluginAsItArrives::AGREEMENT);
});
