<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\Instant;
use Modules\News\Api\AnItem;
use Modules\News\Api\HowMuchIsNew;
use Modules\News\Api\WhatIsNew;
use Modules\Operator\Internal\Presenters\HowTheTabsAreMarked;
use Modules\Operator\Internal\ViewModels\AMarkAsShown;

use function pest;

pest()->group('holds:app-modules/operator/src/Internal/ViewModels/AMarkAsShown.php');

it('marks no tab with a count or a badge where nothing is new', function (): void {
    $marks = new HowTheTabsAreMarked()->of(HowMuchIsNew::none());

    expect([$marks->health->count, $marks->health->badge, $marks->updates->count, $marks->updates->badge])->toBe([0, '', 0, ''])
        ->and([new AMarkAsShown()->count, new AMarkAsShown()->badge])->toBe([0, '']);
});

it('marks Health with the new problems and Updates with the new updates, each count written out as its badge', function (): void {
    $marks = new HowTheTabsAreMarked()->of(HowMuchIsNew::holding(
        WhatIsNew::these(AnItem::anUpdate('0.17.0')),
        WhatIsNew::these(AnItem::aRequest(42)),
        WhatIsNew::these(AnItem::aProblem('service.gluetun', Instant::atEpochSeconds(1_790_000_000)), AnItem::aProblem('service.sonarr', Instant::atEpochSeconds(1_790_000_001))),
    ));

    expect([$marks->health->count, $marks->health->badge, $marks->updates->count, $marks->updates->badge])->toBe([2, '2', 1, '1']);
});
