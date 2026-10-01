<?php

declare(strict_types=1);

use Modules\Connection\Api\LockingAfter;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\LockAfter;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\Screens\HowThisPhoneIsSet;
use Modules\Operator\Internal\ViewModels\AChoiceOfASettingAsShown;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\ConnectionSettingsInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\WhatTheDeviceWouldDraw;

// The phone's own settings, as they are drawn and chosen.

/** The settings screen over a device, with what the phone keeps in memory. */
function thePhonesSettingsOver(
    ADeviceThatKnowsYou $device,
    ?ConnectionSettingsInMemory $kept = null,
    ?AKeychainInMemory $keychain = null,
): HowThisPhoneIsSet {
    return new HowThisPhoneIsSet(new LockingAfter(
        ASealInMemory::working(),
        $kept ?? ConnectionSettingsInMemory::empty(),
        $device,
        FrozenClock::at(Instant::atEpochSeconds(0)),
    ), $keychain ?? AKeychainInMemory::working());
}

it('is served at its own path, and titled Settings', function (): void {
    expect(data_get(NativeRouter::resolve(AScreenWithoutAStack::Settings->value), 'class'))->toBe(HowThisPhoneIsSet::class)
        ->and((string) json_encode(WhatTheDeviceWouldDraw::tree(thePhonesSettingsOver(ADeviceThatKnowsYou::unlocked()))))
        ->toContain(__('settings.title'));
});

it('says Lock after is immediately until the operator chooses, and offers every choice', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(thePhonesSettingsOver(ADeviceThatKnowsYou::unlocked()));

    expect($drawn->said())->toContain(__('settings.lock'))
        ->and($drawn->said())->toContain(__('settings.lock_after'))
        ->and($drawn->said())->toContain(__('settings.lock_after_is'))
        ->and($drawn->offers())->toBe([
            __('settings.after.immediately'),
            __('settings.after.one_minute'),
            __('settings.after.five_minutes'),
            __('settings.after.fifteen_minutes'),
            __('settings.after.one_hour'),
        ])
        ->and(thePhonesSettingsOver(ADeviceThatKnowsYou::unlocked())->lockAfter()->said)->toBe(LockAfter::Immediately->said());
});

it('keeps a choice, tells the device, and draws it as in force', function (): void {
    $device = ADeviceThatKnowsYou::unlocked();
    $kept = ConnectionSettingsInMemory::empty();
    $screen = thePhonesSettingsOver($device, $kept);

    $screen->lockAfterIs(LockAfter::FifteenMinutes->name);

    $chosen = array_values(array_filter($screen->lockAfter()->offered, static fn(AChoiceOfASettingAsShown $choice): bool => $choice->chosen));

    expect($screen->lockAfter()->said)->toBe(LockAfter::FifteenMinutes->said())
        ->and(array_column($chosen, 'word'))->toBe([LockAfter::FifteenMinutes->name])
        ->and($device->allowedAway())->toBe(900)
        ->and($kept->kept()->either(found: static fn(): LockAfter => LockAfter::OneHour, none: static fn(): LockAfter => LockAfter::Immediately))
        ->toBe(LockAfter::OneHour);
});

it('ignores a choice it does not offer', function (): void {
    $device = ADeviceThatKnowsYou::unlocked();
    $screen = thePhonesSettingsOver($device);

    $screen->lockAfterIs('a fortnight');

    expect($device->allowedAway())->toBeNull()
        ->and($screen->lockAfter()->said)->toBe(LockAfter::Immediately->said());
});

it('says the phone keeps nothing between launches where it has no secure storage', function (): void {
    $bare = WhatTheDeviceWouldDraw::by(thePhonesSettingsOver(ADeviceThatKnowsYou::unlocked(), keychain: AKeychainInMemory::withNowhereSafe()));
    $safe = WhatTheDeviceWouldDraw::by(thePhonesSettingsOver(ADeviceThatKnowsYou::unlocked()));

    expect($bare->said())->toContain(__('settings.no_secure_storage'))
        ->and($safe->said())->not->toContain(__('settings.no_secure_storage'));
});
