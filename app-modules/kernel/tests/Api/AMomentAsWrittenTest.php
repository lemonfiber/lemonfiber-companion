<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\Instant;
use Tests\Support\WhatTheTimestampNamed;

/** The seconds since the epoch a timestamp names, or null where it names none. */
function secondsTheTimestampNames(string $written): ?int
{
    return AMomentAsWritten::of($written)->read(
        read: static fn(Instant $moment): WhatTheTimestampNamed => new WhatTheTimestampNamed($moment->epochSeconds()),
        unreadable: static fn(): WhatTheTimestampNamed => new WhatTheTimestampNamed(null),
    )->seconds;
}

it('reads the moment an RFC 3339 timestamp names, to the second', function (string $written, int $seconds): void {
    expect(secondsTheTimestampNames($written))->toBe($seconds);
})->with([
    'as the engine writes it' => ['2026-09-29T21:39:01.530691525Z', 1_790_717_941],
    'with no fraction' => ['2026-09-29T21:39:01Z', 1_790_717_941],
    'with a lower-case t and z' => ['2026-09-29t21:39:01z', 1_790_717_941],
    'with a space for the t' => ['2026-09-29 21:39:01Z', 1_790_717_941],
    'ahead of UTC' => ['2026-09-29T23:39:01+02:00', 1_790_717_941],
    'behind UTC' => ['2026-09-29T17:09:01-04:30', 1_790_717_941],
    'a leap day' => ['2024-02-29T00:00:00Z', 1_709_164_800],
    'the first of March after one' => ['2024-03-01T00:00:00Z', 1_709_251_200],
    'the last day of a year' => ['2025-12-31T23:59:59Z', 1_767_225_599],
    'the epoch itself' => ['1970-01-01T00:00:00Z', 0],
    'a leap second' => ['2016-12-31T23:59:60Z', 1_483_228_800],
]);

it('reads nothing that is not such a moment as a moment', function (string $written): void {
    expect(secondsTheTimestampNames($written))->toBeNull();
})->with([
    'words' => ['not a moment'],
    'nothing' => [''],
    'a date alone' => ['2026-09-29'],
    'no zone' => ['2026-09-29T21:39:01'],
    'a thirteenth month' => ['2026-13-01T00:00:00Z'],
    'a thirtieth of February' => ['2026-02-30T00:00:00Z'],
    'a twenty-fifth hour' => ['2026-09-29T24:00:00Z'],
    'a sixtieth minute' => ['2026-09-29T21:60:00Z'],
    'a sixty-first second' => ['2026-09-29T21:39:61Z'],
    'something after it' => ['2026-09-29T21:39:01Z and more'],
    'a year before the epoch' => ['1969-12-31T23:59:59Z'],
    'the epoch, written from a clock ahead of it' => ['1970-01-01T00:30:00+01:00'],
]);

it('reads a moment the stack wrote of its own as UTC', function (): void {
    expect(AMomentAsWritten::byTheStack('2026-09-29T21:39:01')->read(
        read: static fn(Instant $moment): WhatTheTimestampNamed => new WhatTheTimestampNamed($moment->epochSeconds()),
        unreadable: static fn(): WhatTheTimestampNamed => new WhatTheTimestampNamed(null),
    )->seconds)->toBe(1_790_717_941);
});

it('reads nothing but the stack\'s own form as a moment the stack wrote', function (string $written): void {
    expect(AMomentAsWritten::byTheStack($written)->read(
        read: static fn(Instant $moment): WhatTheTimestampNamed => new WhatTheTimestampNamed($moment->epochSeconds()),
        unreadable: static fn(): WhatTheTimestampNamed => new WhatTheTimestampNamed(null),
    )->seconds)->toBeNull();
})->with([
    'nothing' => [''],
    'with a zone' => ['2026-09-29T21:39:01Z'],
    'with a fraction' => ['2026-09-29T21:39:01.5'],
    'a date alone' => ['2026-09-29'],
    'a thirtieth of February' => ['2026-02-30T00:00:00'],
]);

it('reads a moment written out as whole seconds since the epoch, and nothing else as one', function (string $written, ?int $seconds): void {
    expect(AMomentAsWritten::inSeconds($written)->read(
        read: static fn(Instant $moment): WhatTheTimestampNamed => new WhatTheTimestampNamed($moment->epochSeconds()),
        unreadable: static fn(): WhatTheTimestampNamed => new WhatTheTimestampNamed(null),
    )->seconds)->toBe($seconds);
})->with([
    'a stamp' => ['1761321600', 1_761_321_600],
    'the epoch' => ['0', 0],
    'nothing' => ['', null],
    'a negative' => ['-5', null],
    'letters in it' => ['17613216OO', null],
    'a space after' => ['1761321600 ', null],
    'too long for an integer' => ['1234567890123456789', null],
    'a timestamp' => ['2026-09-29T21:39:01Z', null],
]);
