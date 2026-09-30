<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Connection\Api\LockingAfter;
use Modules\Connection\Internal\TheSettingsAsKept;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\LockAfter;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Unsealed;
use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\ConnectionSettingsInMemory;
use Tests\Support\Fakes\FrozenClock;

/** When the operator chose, in the assertions below. Named for this file. */
const WHEN_THE_OPERATOR_CHOSE = 1_790_000_000;

/** The setting over these, with a clock stopped at when the operator chose. */
function lockingAfterOver(ASealInMemory $seal, ConnectionSettingsInMemory $kept, ADeviceThatKnowsYou $device): LockingAfter
{
    return new LockingAfter($seal, $kept, $device, FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_OPERATOR_CHOSE)));
}

it('is immediately until the operator chooses', function (): void {
    expect(lockingAfterOver(ASealInMemory::working(), ConnectionSettingsInMemory::empty(), ADeviceThatKnowsYou::unlocked())->current())
        ->toBe(LockAfter::Immediately);
});

it('keeps the operator\'s choice and tells the device how long that is', function (): void {
    $device = ADeviceThatKnowsYou::unlocked();
    $setting = lockingAfterOver(ASealInMemory::working(), ConnectionSettingsInMemory::empty(), $device);

    expect($setting->choose(LockAfter::FiveMinutes))->toBe(LockAfter::FiveMinutes)
        ->and($setting->current())->toBe(LockAfter::FiveMinutes)
        ->and($device->allowedAway())->toBe(300);
});

it('tells the device what was chosen before, as the app opens', function (): void {
    $seal = ASealInMemory::working();
    $kept = ConnectionSettingsInMemory::empty();
    lockingAfterOver($seal, $kept, ADeviceThatKnowsYou::unlocked())->choose(LockAfter::OneHour);
    $device = ADeviceThatKnowsYou::unlocked();

    lockingAfterOver($seal, $kept, $device)->toldTheDevice();

    expect($device->allowedAway())->toBe(3600);
});

it('keeps nothing it could read in the clear', function (): void {
    $kept = ConnectionSettingsInMemory::empty();

    lockingAfterOver(ASealInMemory::working(), $kept, ADeviceThatKnowsYou::unlocked())->choose(LockAfter::FifteenMinutes);

    expect($kept->kept()->either(
        found: static fn(SealedPayload $payload): Unsealed => Unsealed::of($payload->forTheStore()),
        none: static fn(): Unsealed => Unsealed::of('none'),
    )->inTheClear())->not->toContain(LockAfter::FifteenMinutes->name);
});

it('still tells the device on a phone that can keep nothing, and forgets the choice after', function (): void {
    $device = ADeviceThatKnowsYou::unlocked();
    $setting = lockingAfterOver(ASealInMemory::withNoSecureStorage(), ConnectionSettingsInMemory::empty(), $device);

    $setting->choose(LockAfter::OneMinute);

    expect($device->allowedAway())->toBe(60)
        ->and($setting->current())->toBe(LockAfter::Immediately);
});

it('reads a setting whose seal no longer opens as immediately', function (): void {
    $kept = ConnectionSettingsInMemory::empty();
    lockingAfterOver(ASealInMemory::working(), $kept, ADeviceThatKnowsYou::unlocked())->choose(LockAfter::OneHour);

    expect(lockingAfterOver(ASealInMemory::working(), $kept, ADeviceThatKnowsYou::unlocked())->current())
        ->toBe(LockAfter::Immediately);
});

it('reads every choice back in the shape it was written in', function (): void {
    foreach (LockAfter::cases() as $after) {
        expect(TheSettingsAsKept::read(Shape::One, TheSettingsAsKept::written($after)))->toBe($after);
    }
});

it('carries a setting it cannot make out over as immediately rather than failing', function (): void {
    foreach (['', 'null', '[]', '{"lock_after":7}', '{"lock_after":"a fortnight"}', '{"lock_after":"one_hour"}'] as $written) {
        expect(TheSettingsAsKept::read(Shape::One, Unsealed::of($written)))->toBe(LockAfter::Immediately, $written);
    }
});

it('says how long each choice is', function (): void {
    expect(array_map(static fn(LockAfter $after): int => $after->howLong()->inSeconds(), LockAfter::cases()))
        ->toBe([0, 60, 300, 900, 3600])
        ->and(LockAfter::standard())->toBe(LockAfter::Immediately)
        ->and(LockAfter::OneHour->said())->toBe('settings.after.one_hour');
});
