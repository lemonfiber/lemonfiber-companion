<?php

declare(strict_types=1);

use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\WhoPutItThere;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\WhatTheDeviceWouldDraw;

// An operator can tell a check a plugin put on the stack from the stack's own,
// and one nobody could attribute from both.

/**
 * A run where the stack's own check ran first and a plugin's second.
 *
 * In that order so a screen deciding from the first row alone — whether to
 * mark, or whether to say what an unmarked row is — gets this one wrong.
 */
function aRunWithAPluginsCheck(WhoPutItThere $second): Report
{
    return Report::of(Overall::Degraded, Findings::of(
        Finding::of(
            Check::of('disk.space'),
            Category::Storage,
            'The disk is nearly full',
            Conclusion::Warned,
            WhatTheCheckSaid::nothingWrong(),
            WhoPutItThere::bundled(),
        ),
        Finding::of(
            Check::of('plex.reachable'),
            Category::Services,
            'Plex answers',
            Conclusion::Warned,
            WhatTheCheckSaid::nothingWrong(),
            $second,
        ),
    ));
}

it('a plugin\'s check says so beside its title, and the report says once what an unmarked row is', function (): void {
    $screen = TheHealthScreenOfTheLoft::theHealthScreen(AStackThatWasAsked::saying(aRunWithAPluginsCheck(WhoPutItThere::plugin('plex'))));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->answer()->marksAnOrigin)->toBeTrue()
        ->and($drawn)->toContain(__('health.origin.plugin', ['named' => 'plex']))
        ->and($drawn)->toContain(__('health.origin.legend'))
        // The stack's own is left unmarked: the legend is what says it.
        ->and($drawn)->not->toContain(__('health.origin.bundled'));
});

it('a check nobody could attribute is marked with the stack\'s reason, never left to read as its own', function (): void {
    $screen = TheHealthScreenOfTheLoft::theHealthScreen(AStackThatWasAsked::saying(aRunWithAPluginsCheck(WhoPutItThere::unknown('the plugin was removed'))));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('health.origin.unknown', ['why' => 'the plugin was removed']))
        ->and($drawn)->toContain(__('health.origin.legend'));
});

it('a report of only the stack\'s own checks marks none and explains nothing', function (): void {
    // The legend explains marks, and a legend under a list with none is a
    // sentence about something that is not on the screen.
    $screen = TheHealthScreenOfTheLoft::theHealthScreen(AStackThatWasAsked::saying(aRunWithAPluginsCheck(WhoPutItThere::bundled())));

    expect($screen->answer()->marksAnOrigin)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('health.origin.legend'));
});
