<?php

declare(strict_types=1);

namespace Modules\Household\Tests\View\Components;

use function app;
use function expect;

use Illuminate\Contracts\Translation\Translator;

use function it;
use function json_encode;

use Modules\Household\Internal\ViewModels\WhatACellSays;
use Modules\Household\View\Components\GuideCell;
use Modules\Household\View\Components\ListedProgramme;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

use function uses;

uses(TestCase::class);

it('letters the programme on now with its times, and says it is on now with its channel', function (): void {
    $cell = WhatACellSays::aProgramme('The news', '19:45–20:30', '6', 120, 'tuneTo(c1)', isOnNow: true, at: 0);
    $outline = WhatMarkupDraws::outline('<x-household::guide-cell :cell="$cell" channel="NPO 1" />', ['cell' => $cell]);

    expect($outline)->toContain('The news')
        ->and($outline)->toContain('19:45–20:30')
        ->and(new GuideCell(app(Translator::class), $cell, 'NPO 1')->named)->toBe('NPO 1, The news, 19:45–20:30, on now');
});

it('letters a later programme with its times, and says it without its channel', function (): void {
    $cell = WhatACellSays::aProgramme('The quiz', '21:00–22:00', '', 240, 'aboutTheProgramme(0, 1)', isOnNow: false, at: 1);
    $outline = WhatMarkupDraws::outline('<x-household::guide-cell :cell="$cell" channel="NPO 1" />', ['cell' => $cell]);

    expect($outline)->toContain('The quiz, 21:00–22:00')
        ->and(new GuideCell(app(Translator::class), $cell, 'NPO 1')->named)->toBe('The quiz, 21:00–22:00');
});

/** Two short programmes drawn together, the first one on now. */
function twoTogether(): WhatACellSays
{
    return WhatACellSays::together(
        '20:30–20:42',
        48,
        'listTheProgrammes(0, 1)',
        WhatACellSays::aProgramme('Weather', '20:30–20:35', '', 20, 'tuneTo(c1)', isOnNow: true, at: 1),
        WhatACellSays::aProgramme('Lottery', '20:35–20:42', '', 28, 'aboutTheProgramme(0, 2)', isOnNow: false, at: 2),
    );
}

it('letters several programmes drawn together with how many and the time they run across, and says both', function (): void {
    $cell = twoTogether();
    $outline = WhatMarkupDraws::outline('<x-household::guide-cell :cell="$cell" channel="NPO 1" />', ['cell' => $cell]);

    expect($outline)->toContain('Two programmes')
        ->and($outline)->toContain('20:30–20:42')
        ->and($outline)->not->toContain('Weather')
        ->and(new GuideCell(app(Translator::class), $cell, 'NPO 1')->named)->toBe('Two programmes, 20:30–20:42');
});

it('draws the cut-off edge of a programme with its title and nothing to press', function (): void {
    $cell = WhatACellSays::aProgramme('The film', '18:30–20:05', '', 20, 'aboutTheProgramme(0, 0)', isOnNow: false, at: 0)->cutOff();
    $drawn = WhatMarkupDraws::drawn('<x-household::guide-cell :cell="$cell" channel="NPO 1" />', ['cell' => $cell]);

    expect($drawn['type'] ?? null)->toBe('column')
        ->and(json_encode($drawn))->not->toContain('pressable')
        ->and(json_encode($drawn))->not->toContain('on_press')
        ->and(WhatMarkupDraws::outline('<x-household::guide-cell :cell="$cell" channel="NPO 1" />', ['cell' => $cell]))->toContain('The film');
});

it('says a programme in the list of several as its own cell would be said', function (): void {
    [$onNow, $later] = twoTogether()->together;

    expect(new ListedProgramme(app(Translator::class), $onNow, 'NPO 1')->named)->toBe('NPO 1, Weather, 20:30–20:35, on now')
        ->and(new ListedProgramme(app(Translator::class), $later, 'NPO 1')->named)->toBe('Lottery, 20:35–20:42')
        ->and(WhatMarkupDraws::outline('<x-household::listed-programme :programme="$later" channel="NPO 1" />', ['later' => $later]))->toContain('Lottery, 20:35–20:42');
});
