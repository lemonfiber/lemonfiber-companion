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

use function sprintf;

use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

use function uses;

// The application is booted here: a render needs the view factory, the
// component namespace, the catalogue and the precompiler.
uses(TestCase::class);

/** A holding as the presenter hands it over. */
function aPosterOf(string $titled, string $year = '1999', string $medium = 'household.medium.film'): WhatOnePosterSays
{
    return WhatOnePosterSays::ofATitle($titled, $medium, $year);
}

it('draws a raised 2:3 tile with its year and kind above its title, and nothing under it', function (): void {
    $markup = '<x-household::poster :poster="$poster" />';
    $data = ['poster' => aPosterOf('Alien')];
    $tile = data_get(WhatMarkupDraws::drawn($markup, $data), 'children.0');

    expect(WhatMarkupDraws::outline($markup, $data))
        ->toBe('pressable{"width":128,"min_height":48}[column{"width":"fill","padding":12,"gap":8,"aspect_ratio":0.6666666666666666,"justify_content":3}[1999 · Film, Alien]]')
        ->and(data_get($tile, 'style.bg_color'))->toBe(ThemeToken::Raised->in(WhoseTheme::Member))
        ->and(data_get($tile, 'style.border_color'))->toBe(ThemeToken::Line->in(WhoseTheme::Member))
        ->and(data_get($tile, 'children.0.props.font_name'))->toBe('DMMono-Medium')
        ->and(data_get($tile, 'children.0.props.color'))->toBe(ThemeToken::Muted->in(WhoseTheme::Member))
        ->and(data_get($tile, 'children.0.props.max_lines'))->toBe(1)
        ->and(data_get($tile, 'children.1.props.font_name'))->toBe('GolosText-ExtraBold')
        ->and(data_get($tile, 'children.1.props.color'))->toBe(ThemeToken::Text->in(WhoseTheme::Member));
});

it('is read as one element, its title, kind and year said once from its label', function (): void {
    expect(data_get(WhatMarkupDraws::drawn('<x-household::poster :poster="$poster" />', ['poster' => aPosterOf('Alien')]), 'props.a11y_label'))
        ->toBe('Alien, Film, 1999');
});

it('says nothing of a year the core could not give, on the tile or to a reader', function (): void {
    $drawn = WhatMarkupDraws::drawn('<x-household::poster :poster="$poster" />', ['poster' => aPosterOf('Alien', year: '', medium: 'household.medium.series')]);

    expect(data_get($drawn, 'props.a11y_label'))->toBe('Alien, Series')
        ->and(data_get($drawn, 'children.0.children.0.props.text'))->toBe('Series');
});

it('letters each step at the brand\'s size it names, and lets it take that step\'s lines', function (string $titled, HowAPosterIsLettered $step): void {
    $title = data_get(WhatMarkupDraws::drawn('<x-household::poster :poster="$poster" />', ['poster' => aPosterOf($titled)]), 'children.0.children.1.props');

    expect(HowAPosterIsLettered::for($titled))->toBe($step)
        ->and(data_get($title, 'text'))->toBe($titled)
        ->and(data_get($title, 'font_size'))->toEqual($step->setAt()->points())
        ->and(data_get($title, 'max_lines'))->toBe($step->linesAtMost());
})->with([
    'a short title, large' => ['Alien', HowAPosterIsLettered::Large],
    'a sentence\'s length, at the middle step' => ['The Lord of the Rings: The Fellowship', HowAPosterIsLettered::Middle],
    'anything longer, small' => ['Dr. Strangelove or: How I Learned to Stop Worrying and Love the Bomb', HowAPosterIsLettered::Small],
]);

it('draws one of their requests with where it stands at the top, and says its name and standing', function (): void {
    $drawn = WhatMarkupDraws::drawn('<x-household::poster :poster="$poster" />', ['poster' => WhatOnePosterSays::ofARequest('Dune', 'household.asked.here')]);

    expect(data_get($drawn, 'props.a11y_label'))->toBe(sprintf('Dune, %s', WhatMarkupDraws::words('household.asked.here')))
        ->and(data_get($drawn, 'children.0.children.0.props.text'))->toBe(__('household.asked.here'))
        ->and(data_get($drawn, 'on_press'))->toBeNull();
});

it('opens its title where it has somewhere to go', function (): void {
    $poster = WhatOnePosterSays::ofATitle('Alien', 'household.medium.film', '1979', '/titles/a1');
    $roads = WhatMarkupDraws::roads('<x-household::poster :poster="$poster" />', ['poster' => $poster]);

    expect($roads)->toHaveCount(1)
        ->and(data_get($roads, '0.uri'))->toBe('/titles/a1');
});

it('opens nothing where it has nowhere to go', function (): void {
    expect(WhatMarkupDraws::roads('<x-household::poster :poster="$poster" />', ['poster' => aPosterOf('Alien')]))->toBe([]);
});
