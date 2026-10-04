<?php

declare(strict_types=1);

namespace Modules\Services\Tests\Api\Queries;

use function expect;
use function implode;
use function it;

use Modules\Kernel\Api\Awaiting;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\WhatItTakesAway;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Services\Api\Queries\WithoutWhatWasLeftOut;

use function sprintf;

use Tests\Support\WhatIsKeptOfServices;

/** The services a listing holds, in order, by name. */
function theServicesNamedIn(Daemons $daemons): string
{
    $names = [];

    foreach ($daemons as $daemon) {
        $names[] = $daemon->name();
    }

    return implode(', ', $names);
}

it('leaves out every service the forms left out, and keeps the rest in the order the stack sent', function (): void {
    expect(theServicesNamedIn(new WithoutWhatWasLeftOut()->over(WhatIsKeptOfServices::aListingWithEveryPart())))->toBe('Jellyfin, Sonarr');
});

it('keeps how the stack is running, what a verb takes away, the forms asked for and what was left out', function (): void {
    $listing = new WithoutWhatWasLeftOut()->over(WhatIsKeptOfServices::aListingWithEveryPart());

    expect($listing->running()->value)->toBe('degraded')
        ->and($listing->active()->count())->toBe(1)
        ->and($listing->leftOut()->count())->toBe(1)
        ->and($listing->disturbs()->forThe(
            WhatToDoWithIt::Stop,
            said: static fn(WhatItTakesAway $takes): Code => $takes->either(
                bounded: static fn(int $seconds): Code => Code::of(sprintf('%d', $seconds)),
                openEnded: static fn(Awaiting $awaiting): Code => Code::of($awaiting->value),
            ),
            unreported: static fn(): Code => Code::of('unreported'),
        )->shown())->toBe('downloads');
});
