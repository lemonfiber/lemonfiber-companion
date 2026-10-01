<?php

declare(strict_types=1);

use Lemonfiber\Native\Screen;
use Modules\Device\Api\PlatformAuth;
use Modules\Kernel\Api\Authenticated;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\DeviceAuth;
use Modules\Kernel\Api\Lock;
use Modules\Kernel\Api\WhenTheLockAsks;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\ADeviceWithAScreenLock;

// The DeviceAuth contract, run against the adapter over a scripted bridge and
// against the fake.
//
// Every test that asserts "the operator unlocked the app" holds an
// `ADeviceThatKnowsYou`, so a fake easier to satisfy than the platform would
// make the lock green against a lock nothing closes. The fallback to the
// passcode cannot be asserted here: it is `WhatUnlocks`, in each native half,
// and `LockRuleTest` and `LockRuleTests` hold it there.

/**
 * Which arm a lock takes, as a word.
 *
 * Named for this file rather than `fold`: the root suites share one namespace,
 * and two functions of the same name are a fatal the moment both load.
 */
function howTheLockAnswered(Lock $lock): string
{
    return $lock->either(
        held: fn(): Code => Code::of('held'),
        open: fn(): Code => Code::of('open'),
    )->shown();
}

/**
 * The adapter and the fake, each with how to make its lock stand again.
 *
 * @return array<string, array{DeviceAuth, Closure(): void}>
 */
function everyDevice(string $which): array
{
    $fake = match ($which) {
        'willing' => ADeviceThatKnowsYou::willing(),
        'refusing' => ADeviceThatKnowsYou::refusing(),
        default => ADeviceThatKnowsYou::withNoScreenLock(),
    };

    $handset = match ($which) {
        'willing' => ADeviceWithAScreenLock::ready(),
        'refusing' => ADeviceWithAScreenLock::refusing(),
        default => ADeviceWithAScreenLock::withNoScreenLock(),
    };

    return [
        'the fake' => [$fake, $fake->standsAgain(...)],
        'the adapter' => [
            new PlatformAuth(new Screen(), Catalogue::words()),
            $handset->bind()->standsAgain(...),
        ],
    ];
}

it('opens the lock where the device authenticated', function (): void {
    foreach (everyDevice('willing') as $which => [$device]) {
        expect(howTheLockAnswered($device->unlock()))->toBe('open', $which)
            ->and(howTheLockAnswered($device->standing()))->toBe('open', $which);
    }
});

it('holds the lock where the device did not authenticate', function (): void {
    // A failure holds the lock. Not "opens with a warning", not "opens and
    // logs" — held, and still held when asked again afterwards.
    foreach (everyDevice('refusing') as $which => [$device]) {
        expect(howTheLockAnswered($device->unlock()))->toBe('held', $which)
            ->and(howTheLockAnswered($device->standing()))->toBe('held', $which);
    }
});

it('stands from a cold start until the device says otherwise', function (): void {
    foreach (everyDevice('willing') as $which => [$device]) {
        expect(howTheLockAnswered($device->standing()))->toBe('held', $which);
    }
});

it('stays held when the lock screen is drawn, whether or not the device asks', function (): void {
    // The device's own prompt is answered later, as the lock opening; being on
    // the glass opens nothing.
    foreach (everyDevice('willing') as $which => [$device]) {
        expect(howTheLockAnswered($device->drawn(WhenTheLockAsks::ByItself)))->toBe('held', $which)
            ->and(howTheLockAnswered($device->drawn(WhenTheLockAsks::OnlyWhenTapped)))->toBe('held', $which);
    }
});

it('opens where the lock is waived', function (): void {
    foreach (everyDevice('refusing') as $which => [$device]) {
        expect(howTheLockAnswered($device->waive()))->toBe('open', $which)
            ->and(howTheLockAnswered($device->standing()))->toBe('open', $which);
    }
});

it('stands again when the app comes back too late', function (): void {
    foreach (everyDevice('willing') as $which => [$device, $standsAgain]) {
        $device->unlock();
        $standsAgain();

        expect(howTheLockAnswered($device->standing()))->toBe('held', $which);
    }
});

it('never stands on a device with no screen lock', function (): void {
    foreach (everyDevice('none') as $which => [$device, $standsAgain]) {
        $standsAgain();

        expect($device->isAvailable())->toBeFalse($which)
            ->and(howTheLockAnswered($device->standing()))->toBe('open', $which);
    }
});

it('says whether the device can authenticate anybody at all', function (): void {
    foreach (everyDevice('willing') as $which => [$device]) {
        expect($device->isAvailable())->toBeTrue($which);
    }
});

it('reads a bridge that answers nothing as a lock that stands', function (): void {
    // Anything but a plain yes is held: a device that is not there, an answer
    // that does not parse, and a key that is missing all keep the lock shut.
    FakeBridge::disable();
    FakeBridge::enable()
        ->respondTo('Lemonfiber.Lock.Standing', ['open' => 'true'])
        ->respondTo('Lemonfiber.Lock.Waive', [])
        ->respondTo('Lemonfiber.Lock.Drawn', 'not json')
        ->respondTo('Lemonfiber.Authenticate', ['authenticated' => 1]);

    $device = new PlatformAuth(new Screen(), Catalogue::words());

    expect(howTheLockAnswered($device->standing()))->toBe('held')
        ->and(howTheLockAnswered($device->waive()))->toBe('held')
        ->and(howTheLockAnswered($device->drawn(WhenTheLockAsks::ByItself)))->toBe('held')
        ->and(howTheLockAnswered($device->unlock()))->toBe('held');
});

it('tells the device whether it may ask by itself, and the reason it shows', function (): void {
    ADeviceWithAScreenLock::ready()->bind();
    $device = new PlatformAuth(new Screen(), Catalogue::words());

    $device->drawn(WhenTheLockAsks::ByItself);
    $device->drawn(WhenTheLockAsks::OnlyWhenTapped);

    $sent = array_column(FakeBridge::current()?->callsTo('Lemonfiber.Lock.Drawn') ?? [], 'params');

    expect(array_column($sent, 'ask'))->toBe([true, false])
        ->and(array_column($sent, 'reason'))->toBe([
            Catalogue::words()->for('device.unlock_reason'),
            Catalogue::words()->for('device.unlock_reason'),
        ]);
});

it('opens a lock only by something the device made', function (): void {
    // `Lock` has two makers and the open one demands an `Authenticated`;
    // `Authenticated` has a private constructor and one maker.
    expect(get_class_methods(Lock::class))->toBe(['held', 'openedBy', 'either'])
        ->and(get_class_methods(Authenticated::class))->toBe(['byTheDevice']);

    $takes = new ReflectionMethod(Lock::class, 'openedBy')->getParameters()[0]->getType();

    expect($takes instanceof ReflectionNamedType ? $takes->getName() : null)
        ->toBe(Authenticated::class);
});

it('asks once per unlock, and counts it', function (): void {
    $device = ADeviceThatKnowsYou::willing();

    $device->unlock();
    $device->unlock();

    expect($device->asked())->toBe(2);
});

it('takes no sentence, so no caller can write one', function (): void {
    expect(new ReflectionMethod(ADeviceThatKnowsYou::class, 'unlock')->getParameters())->toBe([]);
});
