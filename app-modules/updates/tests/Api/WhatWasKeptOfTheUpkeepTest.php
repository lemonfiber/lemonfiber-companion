<?php

declare(strict_types=1);

namespace Modules\Updates\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Upkeep;
use Modules\Updates\Api\WhatWasKeptOfTheUpkeep;

use function sprintf;

use Tests\Support\WhatIsKeptOfUpdates;

/** Which arm a kept reading answered on, and when it was read, as one word. */
function whatWasKeptSaid(WhatWasKeptOfTheUpkeep $kept): string
{
    return $kept->either(
        kept: static fn(Upkeep $upkeep, Instant $readAt): Code => Code::of(sprintf('%s at %d', $upkeep->againstThePins()->value, $readAt->epochSeconds())),
        nothing: static fn(): Code => Code::of('nothing'),
    )->shown();
}

it('hands the kept arm the reading and when it was read', function (): void {
    expect(whatWasKeptSaid(WhatWasKeptOfTheUpkeep::readAt(WhatIsKeptOfUpdates::aReadingOfAStackThatIsCurrent(), Instant::atEpochSeconds(1_790_000_000))))
        ->toBe('current at 1790000000');
});

it('answers nothing with nothing to hand over', function (): void {
    expect(whatWasKeptSaid(WhatWasKeptOfTheUpkeep::nothing()))->toBe('nothing');
});
