<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Zone;

/** 21:39:01 UTC on 29 September 2026, when Amsterdam is on summer time. */
const IN_SUMMER = 1_790_717_941;

/** 21:39:01 UTC on 29 January 2026, when it is not. */
const IN_WINTER = 1_769_722_741;

it('reads the clock as it stood in that zone, summer time included', function (): void {
    $amsterdam = Zone::named('Europe/Amsterdam');

    expect($amsterdam->timeOfDayAt(Instant::atEpochSeconds(IN_SUMMER))->shown())->toBe('23:39:01')
        ->and($amsterdam->timeOfDayAt(Instant::atEpochSeconds(IN_WINTER))->shown())->toBe('22:39:01');
});

it('reads a clock behind UTC into the evening before', function (): void {
    // Just after midnight UTC is the evening before in New York.
    expect(Zone::named('America/New_York')->timeOfDayAt(Instant::atEpochSeconds(1_790_726_400 + 60))->shown())
        ->toBe('20:01:00');
});

it('keeps the name the zone database gives it', function (): void {
    expect(Zone::named('Europe/Amsterdam')->name())->toBe('Europe/Amsterdam');
});

it('reads a name that places nothing as UTC', function (): void {
    $nowhere = Zone::named('Nowhere/Atall');

    expect($nowhere->name())->toBe('UTC')
        ->and($nowhere->timeOfDayAt(Instant::atEpochSeconds(IN_SUMMER))->shown())->toBe('21:39:01');
});

it('reads UTC as no offset at all', function (): void {
    expect(Zone::utc()->timeOfDayAt(Instant::atEpochSeconds(IN_SUMMER))->shown())->toBe('21:39:01');
});
