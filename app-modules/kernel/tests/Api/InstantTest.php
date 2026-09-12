<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\InstantIsBeforeTheEpoch;

const NOON = 1_757_000_000;
const AN_HOUR = 3600;

it('carries the second it was given', function (): void {
    expect(Instant::atEpochSeconds(NOON)->epochSeconds())->toBe(NOON);
});

it('accepts the epoch itself', function (): void {
    // The boundary, and the one a `<= 0` check would get wrong: midnight on
    // 1 January 1970 is a moment like any other.
    expect(Instant::atEpochSeconds(0)->epochSeconds())->toBe(0);
});

it('refuses a moment before the epoch', function (): void {
    expect(fn(): Instant => Instant::atEpochSeconds(-1))->toThrow(InstantIsBeforeTheEpoch::class);
});

it('says which number was before the epoch', function (): void {
    expect(fn(): Instant => Instant::atEpochSeconds(-1))
        ->toThrow(InstantIsBeforeTheEpoch::class, '-1 is before the epoch');
});

it('knows the earlier of two moments', function (): void {
    $earlier = Instant::atEpochSeconds(NOON);
    $later = Instant::atEpochSeconds(NOON + AN_HOUR);

    expect($earlier->isBefore($later))->toBeTrue()
        ->and($later->isBefore($earlier))->toBeFalse();
});

it('knows the later of two moments', function (): void {
    $earlier = Instant::atEpochSeconds(NOON);
    $later = Instant::atEpochSeconds(NOON + AN_HOUR);

    expect($later->isAfter($earlier))->toBeTrue()
        ->and($earlier->isAfter($later))->toBeFalse();
});

it('is neither before nor after itself', function (): void {
    // The boundary both comparisons turn on: an expiry check that read `<=`
    // where it meant `<` would expire a session on the second it was issued.
    $moment = Instant::atEpochSeconds(NOON);
    $same = Instant::atEpochSeconds(NOON);

    expect($moment->isBefore($same))->toBeFalse()
        ->and($moment->isAfter($same))->toBeFalse();
});

it('is the same moment when the second is the same', function (): void {
    expect(Instant::atEpochSeconds(NOON)->is(Instant::atEpochSeconds(NOON)))->toBeTrue();
});

it('is a different moment one second later', function (): void {
    expect(Instant::atEpochSeconds(NOON)->is(Instant::atEpochSeconds(NOON + 1)))->toBeFalse();
});
