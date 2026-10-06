<?php

declare(strict_types=1);

namespace Modules\Household\Tests\View\Components;

use function __;
use function data_get;
use function expect;
use function it;

use Modules\Household\Internal\ViewModels\WhatAShelfRowSays;
use Modules\Household\Internal\ViewModels\WhatOnePosterSays;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

use function uses;

// The application is booted here: a render needs the view factory, the
// component namespace, the catalogue and the precompiler.
uses(TestCase::class);

/** One poster, as the presenter hands it over. */
function aPosterOnARow(string $titled): WhatOnePosterSays
{
    return WhatOnePosterSays::ofATitle($titled, 'household.medium.film', '1999');
}

it('draws its heading, then every poster it holds side by side, scrolled sideways', function (): void {
    $drawn = WhatMarkupDraws::drawn('<x-household::shelf-row :row="$row" />', [
        'row' => new WhatAShelfRowSays('household.shelf.new', [aPosterOnARow('Alien'), aPosterOnARow('Heat')]),
    ]);

    expect(data_get($drawn, 'children.0.props.text'))->toBe(__('household.shelf.new'))
        ->and(data_get($drawn, 'children.1.type'))->toBe('scroll_view')
        ->and(data_get($drawn, 'children.1.props.horizontal'))->toBeTrue()
        ->and(data_get($drawn, 'children.1.props.shows_indicators'))->toBeFalse()
        ->and(data_get($drawn, 'children.1.children.0.children.0.props.a11y_label'))->toBe('Alien, Film, 1999')
        ->and(data_get($drawn, 'children.1.children.0.children.1.props.a11y_label'))->toBe('Heat, Film, 1999')
        ->and(data_get($drawn, 'children.1.children.0.children.2'))->toBeNull();
});

it('draws nothing at all for a row with nothing in it, not even its heading', function (): void {
    $among = '<native:column><x-design::body>Before</x-design::body><x-household::shelf-row :row="$row" /></native:column>';

    expect(WhatMarkupDraws::outline($among, ['row' => new WhatAShelfRowSays('household.shelf.film', [])]))
        ->toBe(WhatMarkupDraws::outline('<native:column><x-design::body>Before</x-design::body></native:column>'));
});
