<?php

declare(strict_types=1);

namespace Modules\Requests\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Requested;
use Modules\Requests\Api\WhatWasKeptOfWhatWasAsked;

use function sprintf;

use Tests\Support\WhatIsKeptOfRequests;

/** Which arm a kept reading answered on, when it was read and when it was handed back, as one word. */
function whatWasKeptOfTheRequestsSaid(WhatWasKeptOfWhatWasAsked $kept): string
{
    return $kept->either(
        kept: static fn(Requested $requested, Instant $readAt, Instant $now): Code => Code::of(sprintf('%d asked at %d, now %d', $requested->count(), $readAt->epochSeconds(), $now->epochSeconds())),
        nothing: static fn(): Code => Code::of('nothing'),
    )->shown();
}

it('hands the kept arm the reading, when it was read, and now', function (): void {
    expect(whatWasKeptOfTheRequestsSaid(WhatWasKeptOfWhatWasAsked::readAt(WhatIsKeptOfRequests::aReadingWithEveryPart(), Instant::atEpochSeconds(10), Instant::atEpochSeconds(20))))
        ->toBe('5 asked at 10, now 20');
});

it('answers nothing with nothing to hand over', function (): void {
    expect(whatWasKeptOfTheRequestsSaid(WhatWasKeptOfWhatWasAsked::nothing()))->toBe('nothing');
});
