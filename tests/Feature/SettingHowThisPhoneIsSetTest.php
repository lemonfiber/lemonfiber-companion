<?php

declare(strict_types=1);

use Bootstrap\Composition\EveryStoreThePhoneKeeps;
use Lemonfiber\Native\Reorderable;
use Modules\Connection\Api\ClearingWhatThePhoneKeeps;
use Modules\Connection\Api\KeepingReadingsFor;
use Modules\Connection\Api\LettingGoOfOldReadings;
use Modules\Connection\Api\LockingAfter;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\ForgetsEverythingKept;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KeptFor;
use Modules\Kernel\Api\KindOfWork;
use Modules\Kernel\Api\LockAfter;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\SecondsIn;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stacks;
use Modules\Operator\Internal\Screens\HowThisPhoneIsSet;
use Modules\Operator\Internal\ViewModels\AChoiceOfASettingAsShown;
use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\ConnectionSettingsInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\ReadingsInMemory;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\StandingsInMemory;
use Tests\Support\Fakes\WorkLeftRunningInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatThePhoneKeeps;

// The phone's own settings, as they are drawn and chosen.

/** The settings screen over a device, with what the phone keeps in memory. */
function thePhonesSettingsOver(
    ADeviceThatKnowsYou $device,
    ?ConnectionSettingsInMemory $kept = null,
    ?AKeychainInMemory $keychain = null,
    ?LettingGoOfOldReadings $readings = null,
    ?ForgetsEverythingKept $everything = null,
    ?Stacks $stacks = null,
): HowThisPhoneIsSet {
    $kept ??= ConnectionSettingsInMemory::empty();
    $locking = new LockingAfter(ASealInMemory::working(), $kept, $device, FrozenClock::at(Instant::atEpochSeconds(0)));

    return new HowThisPhoneIsSet(
        $locking,
        $keychain ?? AKeychainInMemory::working(),
        $readings ?? WhatThePhoneKeeps::nothingTooOld(),
        new ClearingWhatThePhoneKeeps($everything ?? new EveryStoreThePhoneKeeps($kept), $locking),
        $stacks ?? StacksInMemory::working(),
    );
}

/** When the settings are opened, in the readings tests below. */
const WHEN_THE_SETTINGS_OPEN = 1_790_000_000;

/**
 * Readings kept over two stacks: one read a day before the settings open, one read sixty days before.
 *
 * How long they are kept is one of the phone's settings, so it is kept in
 * the settings row the lock's time away is kept in.
 *
 * @return array{LettingGoOfOldReadings, ReadingsInMemory, ConnectionSettingsInMemory}
 */
function readingsADayAndSixtyDaysOld(?ConnectionSettingsInMemory $settings = null): array
{
    $store = ReadingsInMemory::empty();
    $settings ??= ConnectionSettingsInMemory::empty();
    $now = FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_SETTINGS_OPEN));

    foreach (['a-day-old' => 1, 'sixty-days-old' => 60] as $stack => $daysAgo) {
        $store->keep(
            SealedStack::of($stack),
            SealedPayload::of('sealed'),
            Shape::One,
            Instant::atEpochSeconds(WHEN_THE_SETTINGS_OPEN - $daysAgo * SecondsIn::ADay->value),
        );
    }

    return [
        new LettingGoOfOldReadings(new KeepingReadingsFor(ASealInMemory::working(), $settings, $now), $store, $now),
        $store,
        $settings,
    ];
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
        ->and(array_slice($drawn->offers(), 0, count(LockAfter::cases())))->toBe([
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

it('keeps readings for 30 days until the operator chooses, and offers every choice', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(thePhonesSettingsOver(ADeviceThatKnowsYou::unlocked()));

    expect($drawn->said())->toContain(__('settings.readings'))
        ->and($drawn->said())->toContain(__('settings.keep_readings'))
        ->and($drawn->said())->toContain(trans_choice('settings.days', 30))
        ->and($drawn->said())->toContain(__('settings.keep_readings_is'))
        ->and($drawn->offers())->toContain(trans_choice('settings.days', 7))
        ->and($drawn->offers())->toContain(trans_choice('settings.days', 90))
        ->and($drawn->offers())->toContain(__('settings.one_year'))
        ->and($drawn->offers())->toContain(__('settings.until_removed'))
        ->and($drawn->offers())->toContain(__('settings.other'));
});

it('lets go of the readings older than a shorter time the moment it is chosen', function (): void {
    [$readings, $store, $settings] = readingsADayAndSixtyDaysOld();
    $screen = thePhonesSettingsOver(ADeviceThatKnowsYou::unlocked(), readings: $readings);

    $screen->keepReadingsFor('SevenDays');

    expect($store->forgetEverything()->howMany())->toBe(1)
        ->and(trans_choice($screen->keepReadings()->said, $screen->keepReadings()->count))->toBe(trans_choice('settings.days', 7))
        ->and($readings->keptFor()->is(KeptFor::SevenDays))->toBeTrue()
        ->and($settings->forgetEverything()->howMany())->toBe(1);
});

it('keeps every reading where they are kept until removed', function (): void {
    [$readings, $store] = readingsADayAndSixtyDaysOld();
    $screen = thePhonesSettingsOver(ADeviceThatKnowsYou::unlocked(), readings: $readings);

    $screen->keepReadingsFor('UntilRemoved');

    expect($store->forgetEverything()->howMany())->toBe(2)
        ->and($screen->keepReadings()->said)->toBe('settings.until_removed')
        ->and($readings->keptFor()->isUntilRemoved())->toBeTrue();
});

it('opens a field for a count of days, and keeps a count it is given', function (): void {
    [$readings, $store] = readingsADayAndSixtyDaysOld();
    $screen = thePhonesSettingsOver(ADeviceThatKnowsYou::unlocked(), readings: $readings);

    $screen->keepReadingsFor('Other');
    $typing = WhatTheDeviceWouldDraw::by($screen);
    $tree = (string) json_encode(WhatTheDeviceWouldDraw::tree($screen));

    $screen->days = '45';
    $screen->saveDays();
    $chosen = array_values(array_filter($screen->keepReadings()->offered, static fn(AChoiceOfASettingAsShown $choice): bool => $choice->chosen));

    expect($typing->said())->toContain(__('settings.days_label'))
        ->and($typing->said())->toContain(__('settings.days_between', ['fewest' => 1, 'most' => 365]))
        ->and($typing->offers())->toContain(__('settings.save'))
        ->and($tree)->toContain('"keyboard":"number"')
        ->and($screen->typingDays)->toBeFalse()
        ->and(trans_choice($screen->keepReadings()->said, $screen->keepReadings()->count))->toBe(trans_choice('settings.days', 45))
        ->and(array_column($chosen, 'word'))->toBe(['Other'])
        ->and($store->forgetEverything()->howMany())->toBe(1);
});

it('refuses a count of days it cannot keep readings for, and keeps the field open', function (string $typed): void {
    [$readings, $store] = readingsADayAndSixtyDaysOld();
    $screen = thePhonesSettingsOver(ADeviceThatKnowsYou::unlocked(), readings: $readings);

    $screen->keepReadingsFor('Other');
    $screen->days = $typed;
    $screen->saveDays();

    expect($screen->daysRefused)->toBeTrue()
        ->and($screen->typingDays)->toBeTrue()
        ->and($readings->keptFor()->is(KeptFor::ThirtyDays))->toBeTrue()
        ->and($store->forgetEverything()->howMany())->toBe(2);
})->with(['none' => '0', 'more than a year' => '366', 'not a number' => 'a week', 'nothing' => '']);

it('ignores a choice of how long readings are kept it does not offer', function (): void {
    [$readings] = readingsADayAndSixtyDaysOld();
    $screen = thePhonesSettingsOver(ADeviceThatKnowsYou::unlocked(), readings: $readings);

    $screen->keepReadingsFor('a fortnight');

    expect($readings->keptFor()->is(KeptFor::ThirtyDays))->toBeTrue()
        ->and($screen->typingDays)->toBeFalse();
});

it('asks on this screen before it clears saved data, and keeps it when told to', function (): void {
    $kept = ConnectionSettingsInMemory::empty();
    $screen = thePhonesSettingsOver(ADeviceThatKnowsYou::unlocked(), $kept);
    $screen->lockAfterIs(LockAfter::OneHour->name);

    $before = WhatTheDeviceWouldDraw::by($screen);
    $screen->askToClear();
    $asking = WhatTheDeviceWouldDraw::by($screen);
    $screen->keepSavedData();

    expect($before->said())->toContain(__('settings.saved_data'))
        ->and($before->offers())->toContain(__('settings.clear_saved_data'))
        ->and($before->said())->not->toContain(__('settings.clear_confirm'))
        ->and($asking->said())->toContain(__('settings.clear_confirm'))
        ->and($asking->offers())->toContain(__('settings.clear'))
        ->and($asking->offers())->toContain(__('settings.keep_it'))
        ->and($screen->lockAfter()->said)->toBe(LockAfter::OneHour->said())
        ->and($kept->forgetEverything()->howMany())->toBe(1);
});

it('clears every reading, setting and marker, and draws every setting at its standard', function (): void {
    $device = ADeviceThatKnowsYou::unlocked();
    $kept = ConnectionSettingsInMemory::empty();
    [$readings, $store] = readingsADayAndSixtyDaysOld($kept);
    $stack = StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST)));
    $standings = StandingsInMemory::working()->lastHeard($stack, HowItStands::Healthy, Instant::atEpochSeconds(WHEN_THE_SETTINGS_OPEN));
    $left = WorkLeftRunningInMemory::working()->leftBefore($stack, KindOfWork::Walkthrough, Job::named('a-walk'));
    $screen = thePhonesSettingsOver($device, $kept, readings: $readings, everything: new EveryStoreThePhoneKeeps($kept, $store, $standings, $left));

    $screen->lockAfterIs(LockAfter::OneHour->name);
    $screen->keepReadingsFor(KeptFor::NinetyDays->name);
    $screen->askToClear();
    $screen->clearSavedData();

    expect($screen->confirmingTheClear)->toBeFalse()
        ->and($screen->lockAfter()->said)->toBe(LockAfter::Immediately->said())
        ->and($readings->keptFor()->is(KeptFor::ThirtyDays))->toBeTrue()
        ->and($device->allowedAway())->toBe(0)
        ->and($store->forgetEverything()->howMany())->toBe(0)
        ->and($standings->forgetEverything()->howMany())->toBe(0)
        ->and($left->forgetEverything()->howMany())->toBe(0);
});

/** Two stacks on the phone, the loft paired first. */
function theLoftAndTheAttic(): StacksInMemory
{
    return StacksInMemory::holding(
        Stack::of(StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))), StackName::of('The loft'), Address::of('https://192.168.1.42'), Fingerprint::of(str_repeat('a', Fingerprint::CHARACTERS))),
        Stack::of(StackId::of(Nonce::of(str_repeat('b', Nonce::SHORTEST))), StackName::of('The attic'), Address::of('https://192.168.1.43'), Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS))),
    );
}

/**
 * Every node of a frame, the frame's own first.
 *
 * @param array<mixed> $node
 *
 * @return list<array<mixed>>
 */
function nodesOfTheSettings(array $node): array
{
    $found = [$node];
    $children = is_array($node['children'] ?? null) ? $node['children'] : [];

    foreach ($children as $child) {
        $found = [...$found, ...(is_array($child) ? nodesOfTheSettings($child) : [])];
    }

    return $found;
}

/**
 * What the list to put in order carries, as the phone is handed it.
 *
 * @return array<mixed>
 */
function theOrderAsDrawn(HowThisPhoneIsSet $screen): array
{
    foreach (nodesOfTheSettings(WhatTheDeviceWouldDraw::tree($screen)) as $node) {
        if (($node['type'] ?? null) === Reorderable::TYPE && is_array($node['props'] ?? null)) {
            return $node['props'];
        }
    }

    return [];
}

it('offers the stacks under Stack order in their order, with the moves a screen reader offers', function (): void {
    $screen = thePhonesSettingsOver(ADeviceThatKnowsYou::unlocked(), stacks: theLoftAndTheAttic());
    $drawn = theOrderAsDrawn($screen);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('settings.stacks'), __('settings.stack_order'))
        ->and($drawn['names'] ?? null)->toBe(['The loft', 'The attic'])
        ->and($drawn['move_up'] ?? null)->toBe([__('settings.move_up', ['name' => 'The loft']), __('settings.move_up', ['name' => 'The attic'])])
        ->and($drawn['move_down'] ?? null)->toBe([__('settings.move_down', ['name' => 'The loft']), __('settings.move_down', ['name' => 'The attic'])])
        ->and($drawn['keys'] ?? null)->toBe([str_repeat('a', Nonce::SHORTEST), str_repeat('b', Nonce::SHORTEST)]);
});

it('puts the stacks in the order the list sent, passing over a key that names no stack', function (): void {
    $stacks = theLoftAndTheAttic();
    $screen = thePhonesSettingsOver(ADeviceThatKnowsYou::unlocked(), stacks: $stacks);

    $screen->putStacksInOrder(Reorderable::sent(['', str_repeat('b', Nonce::SHORTEST), str_repeat('a', Nonce::SHORTEST)]));

    expect(theOrderAsDrawn($screen)['names'] ?? null)->toBe(['The attic', 'The loft']);
});
