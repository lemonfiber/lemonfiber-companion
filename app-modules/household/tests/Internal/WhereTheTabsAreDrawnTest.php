<?php

declare(strict_types=1);

namespace Modules\Household\Tests\Internal;

use function expect;
use function it;

use Modules\Household\Internal\Screens\LookingForATitle;
use Modules\Household\Internal\Screens\WhatYouAreOwed;
use Modules\Household\Internal\Screens\WhatYouCanWatch;
use Modules\Household\Internal\Screens\YourCornerOfTheHouse;
use Modules\Household\Internal\WhereTheTabsAreDrawn;
use Modules\Wayfinding\Api\TheHouseholdsTabs;
use stdClass;

it('draws Home, Search, Requests and Profile on the shelf, the search, the requests and the profile', function (): void {
    expect(WhereTheTabsAreDrawn::classOf(TheHouseholdsTabs::Home))->toBe(WhatYouCanWatch::class)
        ->and(WhereTheTabsAreDrawn::classOf(TheHouseholdsTabs::Search))->toBe(LookingForATitle::class)
        ->and(WhereTheTabsAreDrawn::classOf(TheHouseholdsTabs::Requests))->toBe(WhatYouAreOwed::class)
        ->and(WhereTheTabsAreDrawn::classOf(TheHouseholdsTabs::Profile))->toBe(YourCornerOfTheHouse::class);
});

it('puts each tab\'s screen under that tab, and says it is drawn by a tab', function (TheHouseholdsTabs $tab): void {
    $screen = WhereTheTabsAreDrawn::classOf($tab);

    expect(WhereTheTabsAreDrawn::owning($screen))->toBe($tab)
        ->and(WhereTheTabsAreDrawn::drawnBy($screen))->toBeTrue();
})->with(TheHouseholdsTabs::cases());

it('puts a screen no tab draws under no tab', function (): void {
    expect(WhereTheTabsAreDrawn::owning(stdClass::class))->toBeNull()
        ->and(WhereTheTabsAreDrawn::drawnBy(stdClass::class))->toBeFalse();
});
