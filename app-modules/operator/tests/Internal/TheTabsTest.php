<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal;

use function array_map;
use function expect;
use function it;

use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\Internal\Screens\WhatThisServiceSaid;
use Modules\Operator\Internal\Screens\WhatThisStackIsSetTo;
use Modules\Operator\Internal\Screens\WhatThisStackRuns;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;
use Modules\Operator\Internal\Screens\WhatWouldBePutRight;
use Modules\Operator\Internal\TheTabs;
use Modules\Stacks\Api\AStacksScreen;

it('holds four tabs, Health, Services, Updates and Repairs, in that order', function (): void {
    expect(array_map(static fn(TheTabs $tab): AStacksScreen => $tab->screen(), TheTabs::cases()))
        ->toBe([AStacksScreen::Health, AStacksScreen::Services, AStacksScreen::Updates, AStacksScreen::Repairs]);
});

it('draws each tab\'s screen as its root, and a screen of its own as opened on top of it', function (): void {
    expect(TheTabs::drawnBy(HowThisStackIs::class))->toBeTrue()
        ->and(TheTabs::drawnBy(WhatThisStackRuns::class))->toBeTrue()
        ->and(TheTabs::drawnBy(HowCurrentThisStackIs::class))->toBeTrue()
        ->and(TheTabs::drawnBy(WhatWouldBePutRight::class))->toBeTrue()
        ->and(TheTabs::drawnBy(WhatToDoWithThis::class))->toBeFalse()
        ->and(TheTabs::drawnBy(WhatThisStackIsSetTo::class))->toBeFalse();
});

it('marks Services for what one service is doing and what it said, and no tab for a screen the menu opens', function (): void {
    expect(TheTabs::owning(WhatToDoWithThis::class))->toBe(TheTabs::Services)
        ->and(TheTabs::owning(WhatThisServiceSaid::class))->toBe(TheTabs::Services)
        ->and(TheTabs::owning(HowThisStackIs::class))->toBe(TheTabs::Health)
        ->and(TheTabs::owning(HowCurrentThisStackIs::class))->toBe(TheTabs::Updates)
        ->and(TheTabs::owning(WhatWouldBePutRight::class))->toBe(TheTabs::Repairs)
        ->and(TheTabs::owning(WhatThisStackIsSetTo::class))->toBeNull();
});
