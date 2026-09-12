<?php

declare(strict_types=1);

use Lemonfiber\Native\Screen;
use Modules\Device\Api\PlatformAuth;
use Modules\Kernel\Api\Authenticated;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\DeviceAuth;
use Modules\Kernel\Api\Lock;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\ADeviceWithAScreenLock;

// The DeviceAuth contract, run against the adapter and against the fake.
//
// `G2`'s shape. Every test that ever asserts "the operator unlocked the app"
// will hold an `ADeviceThatKnowsYou` and never see a prompt, so a fake easier to
// satisfy than the platform would make `N4-R7` green against a lock nothing
// closes.
//
// `N4-R8`'s first clause — a biometric failure falls back to the device passcode
// — cannot be asserted here and is not pretended at. It is one constant chosen
// in the native half, where the platform is told what it may accept, and the
// only honest statement about it from PHP is the one in `PlatformAuth`'s own
// docblock naming the line. What *is* asserted here is the second clause, which
// is a property of the type: there is no answer meaning "open, but nobody
// checked".

/**
 * Which arm a lock takes, as a word.
 *
 * Named for this file rather than `fold`: the root suites share one namespace,
 * and two functions of the same name are a fatal the moment both load (`G10`).
 */
function howTheLockAnswered(Lock $lock): string
{
    return $lock->either(
        held: fn(): Code => Code::of('held'),
        open: fn(): Code => Code::of('open'),
    )->shown();
}

/** @return array<string, Closure(): DeviceAuth> */
function everyDevice(bool $willing): array
{
    return [
        'the fake' => fn(): DeviceAuth => $willing
            ? ADeviceThatKnowsYou::willing()
            : ADeviceThatKnowsYou::refusing(),
        'the adapter' => function () use ($willing): DeviceAuth {
            ADeviceWithAScreenLock::ready()->bind();

            // The adapter answers from whether the prompt was *raised*, so a
            // device that refuses is modelled by a bridge that does not raise
            // one. That is the honest seam: PHP never sees the operator's
            // answer, which arrives as an event.
            if (! $willing) {
                FakeBridge::disable();
                FakeBridge::enable()
                    ->respondTo('Lemonfiber.CanAuthenticate', ['canAuthenticate' => true])
                    ->respondTo('Lemonfiber.Authenticate', ['acknowledged' => false]);
            }

            return new PlatformAuth(new Screen(), Catalogue::words());
        },
    ];
}

it('N4-R7 — opens the lock where the device authenticated', function (): void {
    foreach (everyDevice(willing: true) as $which => $make) {
        expect(howTheLockAnswered($make()->unlock()))->toBe('open', $which);
    }
});

it('N4-R8 — holds the lock where it did not', function (): void {
    // The clause that matters most and is easiest to get wrong: a failure holds
    // the lock. Not "opens with a warning", not "opens and logs" — held.
    foreach (everyDevice(willing: false) as $which => $make) {
        expect(howTheLockAnswered($make()->unlock()))->toBe('held', $which);
    }
});

it('says whether the device can authenticate anybody at all', function (): void {
    ADeviceWithAScreenLock::ready()->bind();

    expect(ADeviceThatKnowsYou::willing()->isAvailable())->toBeTrue()
        ->and(new PlatformAuth(new Screen(), Catalogue::words())->isAvailable())->toBeTrue();

    ADeviceWithAScreenLock::withNoScreenLock()->bind();

    expect(ADeviceThatKnowsYou::withNoScreenLock()->isAvailable())->toBeFalse()
        ->and(new PlatformAuth(new Screen(), Catalogue::words())->isAvailable())->toBeFalse();
});

it('N4-R8 — a lock can only be opened by something the device made', function (): void {
    // The second clause, as a property of the types rather than of a code path.
    // `Lock` has two makers and the open one demands an `Authenticated`;
    // `Authenticated` has a private constructor and one maker. So "fall back to
    // unlocked" is not a mistake somebody can make here — there is no value
    // that means it.
    expect(get_class_methods(Lock::class))->toBe(['held', 'openedBy', 'either'])
        ->and(get_class_methods(Authenticated::class))->toBe(['byTheDevice']);

    $takes = new ReflectionMethod(Lock::class, 'openedBy')->getParameters()[0]->getType();

    expect($takes instanceof ReflectionNamedType ? $takes->getName() : null)
        ->toBe(Authenticated::class);
});

it('N4-R19 — asks once per unlock, and counts it', function (): void {
    // `N4-R19` is about how often somebody is interrupted, so the count is what
    // a caller has to be able to assert on. The rule deciding *when* to call
    // this lives in `LockRule`, on the native side, with seven tests per
    // platform; this is the seam that makes the decision observable from PHP.
    $device = ADeviceThatKnowsYou::willing();

    $device->unlock();
    $device->unlock();

    expect($device->asked())->toBe(2);
});

it('takes no sentence, so no caller can write one', function (): void {
    // `D2` refused `unlock(string $reason)` as a bare primitive and was right
    // for a better reason than it states: a caller that may pass the sentence is
    // a caller that may write it, and `L1` says the words come from the
    // translator. With no parameter there is nowhere for a literal to get in.
    expect(new ReflectionMethod(ADeviceThatKnowsYou::class, 'unlock')->getParameters())->toBe([]);
});
