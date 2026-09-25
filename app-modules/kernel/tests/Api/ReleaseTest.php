<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowAServiceTookIt;
use Modules\Kernel\Api\HowItEnded;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowToUndoIt;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\VersionIsBlank;
use Modules\Kernel\Api\WhatAReleaseDelivers;

it('refuses a release with no version to call it by', function (): void {
    // A payload short of the name is the stack's half of the conversation gone
    // wrong, not a release with an empty name — and a screen handed one would
    // draw a row an operator could tap with nothing behind it.
    expect(fn(): Release => Release::called('   ', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing()))
        ->toThrow(VersionIsBlank::class);
});

it('takes the version as it was written, without the space around it', function (): void {
    expect(Release::called('  4.1.0  ', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing())->version())->toBe('4.1.0');
});

it('hands out a list however it was built', function (): void {
    // A PHP variadic is **not** a list. Named arguments carry their names
    // through as keys, so a collection that took what arrived would hand out a
    // map — and a template indexing `[0]` would find nothing while `count()`
    // said there was something there.
    $releases = Releases::these(
        newest: Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing()),
        older: Release::called('4.0.16', noticeable: false, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing()),
    );

    expect(keysOf($releases))->toBe([0, 1]);
});

it('hands out a list of services however it was built', function (): void {
    expect(keysOf(Services::these(
        first: ServiceId::called('jellyfin'),
        second: ServiceId::called('sonarr'),
    )))->toBe([0, 1]);
});

it('hands out a list of applied services however it was built', function (): void {
    expect(keysOf(HowServicesTookIt::these(
        first: HowAServiceTookIt::of(ServiceId::called('jellyfin'), HowItEnded::Updated, HowToUndoIt::Rollback),
        second: HowAServiceTookIt::of(ServiceId::called('sonarr'), HowItEnded::NotStarted, HowToUndoIt::Restore),
    )))->toBe([0, 1]);
});

it('hands out a list after narrowing the applied services', function (): void {
    $went = HowServicesTookIt::these(
        HowAServiceTookIt::of(ServiceId::called('jellyfin'), HowItEnded::Updated, HowToUndoIt::Rollback),
        HowAServiceTookIt::of(ServiceId::called('sonarr'), HowItEnded::NotStarted, HowToUndoIt::Restore),
    )->thatDidNotArrive();

    expect(keysOf($went))->toBe([0]);
});

/**
 * The keys a collection actually hands out.
 *
 * Read off the iterator rather than off a count, because a count is the one
 * thing a map and a list agree about.
 *
 * @param iterable<array-key, mixed> $collection
 *
 * @return list<array-key>
 */
function keysOf(iterable $collection): array
{
    $keys = [];

    foreach ($collection as $key => $held) {
        $keys[] = $key;
    }

    return $keys;
}
