<?php

declare(strict_types=1);

namespace Modules\Household\Tests\View\Components;

use function __;
use function data_get;
use function expect;
use function it;

use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhoseTheme;
use Modules\Household\Internal\ViewModels\HowAPosterIsLettered;
use Modules\Household\Internal\ViewModels\WhatOnePosterSays;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

use function uses;

// The application is booted here: a render needs the view factory, the
// component namespace, the catalogue and the precompiler.
uses(TestCase::class);

/** The newest title, as the presenter hands it over, opening its own screen. */
function theNewestTitle(string $titled = 'Alien'): WhatOnePosterSays
{
    return WhatOnePosterSays::ofATitle($titled, 'household.medium.film', '1979', '/titles/a1');
}

it('draws a raised 16:9 tile, said as one element, then Play that waits, its reason, and More', function (): void {
    $drawn = WhatMarkupDraws::drawn('<x-household::hero :poster="$poster" />', ['poster' => theNewestTitle()]);
    $tile = data_get($drawn, 'children.0.children.0');

    expect(data_get($drawn, 'children.0.type'))->toBe('pressable')
        ->and(data_get($drawn, 'children.0.props.a11y_label'))->toBe(__('household.hero.reads', ['reads' => 'Alien, Film, 1979']))
        ->and(data_get($drawn, 'children.0.on_press'))->toBeNull()
        ->and(data_get($tile, 'layout.aspect_ratio'))->toEqual(16 / 9)
        ->and(data_get($tile, 'style.bg_color'))->toBe(ThemeToken::Raised->in(WhoseTheme::Member))
        ->and(data_get($tile, 'children.0.props.text'))->toBe(__('household.hero.above', ['line' => '1979 · Film']))
        ->and(data_get($tile, 'children.1.props.text'))->toBe('Alien')
        ->and(data_get($drawn, 'children.1.props.label'))->toBe(__('household.title.play'))
        ->and(data_get($drawn, 'children.1.props.a11y_label'))->toBe(__('household.title.play_named', ['title' => 'Alien']))
        ->and(data_get($drawn, 'children.1.props.disabled'))->toBeTrue()
        ->and(data_get($drawn, 'children.2.props.text'))->toBe(__('household.title.cannot_play'))
        ->and(data_get($drawn, 'children.3.props.a11y_label'))->toBe(__('household.hero.more_named', ['title' => 'Alien']));
});

it('opens the title from More', function (): void {
    $roads = WhatMarkupDraws::roads('<x-household::hero :poster="$poster" />', ['poster' => theNewestTitle()]);

    expect($roads)->toHaveCount(1)
        ->and(data_get($roads, '0.uri'))->toBe('/titles/a1');
});

it('draws More that waits where the title opens nothing', function (): void {
    $drawn = WhatMarkupDraws::drawn('<x-household::hero :poster="$poster" />', ['poster' => WhatOnePosterSays::ofATitle('Alien', 'household.medium.film', '1979')]);

    expect(data_get($drawn, 'children.3.props.disabled'))->toBeTrue();
});

it('letters each step a size up from its poster, and lets it take that step\'s lines', function (string $titled, HowAPosterIsLettered $step): void {
    $title = data_get(WhatMarkupDraws::drawn('<x-household::hero :poster="$poster" />', ['poster' => theNewestTitle($titled)]), 'children.0.children.0.children.1.props');

    expect(data_get($title, 'font_size'))->toEqual($step->setOnTheHero()->points())
        ->and(data_get($title, 'max_lines'))->toBe($step->linesOnTheHero());
})->with([
    'a short title' => ['Alien', HowAPosterIsLettered::Large],
    'a sentence\'s length' => ['The Lord of the Rings: The Fellowship', HowAPosterIsLettered::Middle],
    'anything longer' => ['Dr. Strangelove or: How I Learned to Stop Worrying and Love the Bomb', HowAPosterIsLettered::Small],
]);
