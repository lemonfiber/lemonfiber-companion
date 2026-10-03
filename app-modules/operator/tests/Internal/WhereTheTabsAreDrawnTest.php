<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal;

use function expect;
use function it;

use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\Internal\Screens\WhatThisServiceSaid;
use Modules\Operator\Internal\Screens\WhatThisStackIsSetTo;
use Modules\Operator\Internal\Screens\WhatThisStackRuns;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;
use Modules\Operator\Internal\Screens\WhatWouldBePutRight;
use Modules\Operator\Internal\WhereTheTabsAreDrawn;
use Modules\Wayfinding\Api\TheTabs;

it('draws each tab\'s screen as its root, and a screen of its own as opened on top of it', function (): void {
    expect(WhereTheTabsAreDrawn::drawnBy(HowThisStackIs::class))->toBeTrue()
        ->and(WhereTheTabsAreDrawn::drawnBy(WhatThisStackRuns::class))->toBeTrue()
        ->and(WhereTheTabsAreDrawn::drawnBy(HowCurrentThisStackIs::class))->toBeTrue()
        ->and(WhereTheTabsAreDrawn::drawnBy(WhatWouldBePutRight::class))->toBeTrue()
        ->and(WhereTheTabsAreDrawn::drawnBy(WhatToDoWithThis::class))->toBeFalse()
        ->and(WhereTheTabsAreDrawn::drawnBy(WhatThisStackIsSetTo::class))->toBeFalse();
});

it('marks Services for what one service is doing and what it said, and no tab for a screen the menu opens', function (): void {
    expect(WhereTheTabsAreDrawn::owning(WhatToDoWithThis::class))->toBe(TheTabs::Services)
        ->and(WhereTheTabsAreDrawn::owning(WhatThisServiceSaid::class))->toBe(TheTabs::Services)
        ->and(WhereTheTabsAreDrawn::owning(HowThisStackIs::class))->toBe(TheTabs::Health)
        ->and(WhereTheTabsAreDrawn::owning(HowCurrentThisStackIs::class))->toBe(TheTabs::Updates)
        ->and(WhereTheTabsAreDrawn::owning(WhatWouldBePutRight::class))->toBe(TheTabs::Repairs)
        ->and(WhereTheTabsAreDrawn::owning(WhatThisStackIsSetTo::class))->toBeNull();
});

it('names the class that draws each tab, and that class is the tab\'s own', function (): void {
    foreach (TheTabs::cases() as $tab) {
        expect(WhereTheTabsAreDrawn::owning(WhereTheTabsAreDrawn::classOf($tab)))->toBe($tab);
    }
});
