<?php

declare(strict_types=1);

namespace Modules\Services\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Instant;
use Modules\Services\Api\WhatWasKeptOfWhatItRuns;

use function sprintf;

use Tests\Support\WhatIsKeptOfServices;

/** Which arm a kept listing answered on, when it was read and when it was handed back, as one word. */
function whatWasKeptOfTheListingSaid(WhatWasKeptOfWhatItRuns $kept): string
{
    return $kept->either(
        kept: static fn(Daemons $daemons, Instant $readAt, Instant $now): Code => Code::of(sprintf('%s at %d, now %d', $daemons->running()->value, $readAt->epochSeconds(), $now->epochSeconds())),
        nothing: static fn(): Code => Code::of('nothing'),
    )->shown();
}

it('hands the kept arm the listing, when it was read, and now', function (): void {
    expect(whatWasKeptOfTheListingSaid(WhatWasKeptOfWhatItRuns::readAt(WhatIsKeptOfServices::aListingOfNothing(), Instant::atEpochSeconds(10), Instant::atEpochSeconds(20))))
        ->toBe('inactive at 10, now 20');
});

it('answers nothing with nothing to hand over', function (): void {
    expect(whatWasKeptOfTheListingSaid(WhatWasKeptOfWhatItRuns::nothing()))->toBe('nothing');
});
