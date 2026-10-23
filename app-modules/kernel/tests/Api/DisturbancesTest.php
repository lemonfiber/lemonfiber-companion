<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\Awaiting;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\WhatItTakesAway;
use Modules\Kernel\Api\WhatToDoWithIt;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** What a verb takes away, as one word a case can compare. */
function whatTheVerbTakesAway(Disturbances $disturbs, WhatToDoWithIt $doing): string
{
    return $disturbs->forThe(
        $doing,
        said: static fn(WhatItTakesAway $takes): TheWordCarriedOut => $takes->either(
            bounded: static fn(int $seconds): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%d', $seconds)),
            openEnded: static fn(Awaiting $awaiting): TheWordCarriedOut => new TheWordCarriedOut($awaiting->value),
        ),
        unreported: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unreported'),
    )->said;
}

it('says what each verb takes away, a fetch only once the reading said it, and keeps the rest when it does', function (): void {
    $kept = Disturbances::of(WhatItTakesAway::atMost(180), WhatItTakesAway::until(Awaiting::Downloads), WhatItTakesAway::atMost(45));
    $read = $kept->whileFetching(WhatItTakesAway::atMost(1800));

    expect(array_map(static fn(WhatToDoWithIt $doing): string => whatTheVerbTakesAway($kept, $doing), WhatToDoWithIt::cases()))
        ->toBe(array_map(static fn(WhatToDoWithIt $doing): string => match ($doing) {
            WhatToDoWithIt::Start => '180',
            WhatToDoWithIt::Stop => 'downloads',
            WhatToDoWithIt::Restart => '45',
            WhatToDoWithIt::Pull => 'unreported',
        }, WhatToDoWithIt::cases()))
        ->and(whatTheVerbTakesAway($read, WhatToDoWithIt::Pull))->toBe('1800')
        ->and([whatTheVerbTakesAway($read, WhatToDoWithIt::Start), whatTheVerbTakesAway($read, WhatToDoWithIt::Stop), whatTheVerbTakesAway($read, WhatToDoWithIt::Restart)])->toBe(['180', 'downloads', '45']);
});
