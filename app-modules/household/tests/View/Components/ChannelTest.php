<?php

declare(strict_types=1);

namespace Modules\Household\Tests\View\Components;

use function expect;
use function it;

use Modules\Household\Internal\ViewModels\WhatOneChannelSays;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

use function uses;

uses(TestCase::class);

/** A channel as the presenter hands it over. */
function aChannelTile(string $number = '1', string $programme = 'The news', string $until = '20:30', string $certificate = '6'): WhatOneChannelSays
{
    return new WhatOneChannelSays('c1', 'NPO 1', $number, $programme, $until, $certificate, 'tuneTo(c1)');
}

it('letters its number and name above what is on, and until when below', function (): void {
    $outline = WhatMarkupDraws::outline('<x-household::channel :channel="$channel" />', ['channel' => aChannelTile()]);

    expect($outline)->toContain('1 · NPO 1')
        ->and($outline)->toContain('The news')
        ->and($outline)->toContain('until 20:30')
        ->and($outline)->toContain('1 · NPO 1, The news, until 20:30');
});

it('draws its name alone where it has no number, and its certificate where the guide gives no end', function (): void {
    $outline = WhatMarkupDraws::outline('<x-household::channel :channel="$channel" />', ['channel' => aChannelTile(number: '', until: '')]);

    expect($outline)->toContain('NPO 1, The news')
        ->and($outline)->not->toContain('·')
        ->and($outline)->not->toContain('until')
        ->and($outline)->toContain('6');
});

it('says the guide has nothing where nothing is on', function (): void {
    $outline = WhatMarkupDraws::outline('<x-household::channel :channel="$channel" />', ['channel' => aChannelTile(programme: '', until: '', certificate: '')]);

    expect($outline)->toContain('1 · NPO 1, Nothing in the guide right now');
});
