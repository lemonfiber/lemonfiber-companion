<?php

declare(strict_types=1);

namespace Modules\Household\Tests\View\Components;

use function app;
use function expect;

use Illuminate\Contracts\Translation\Translator;

use function it;

use Modules\Household\Internal\ViewModels\WhatACellSays;
use Modules\Household\View\Components\GuideCell;
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
