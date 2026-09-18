<?php

declare(strict_types=1);

use Modules\Connection\Api\Opening;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Operator\Internal\Screens\YourStacks;
use Tests\Support\Fakes\ADeviceOnANetwork;
use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\VerdictsInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// What the lock is for, and what it hides.
//
// Asked against the rendered tree rather than the template text, because all
// three are claims about a *frame*. A template carries both arms of its own
// `@if` and reads as a screen that says everything on every branch; which of
// them the device draws is decided at render, which is where this asks.
//
// The three are one behaviour seen from three sides: when the
// prompt is asked at all, what decides it, and what the
// frame may carry while it stands.

/** The moment a launch is read at. Named for this file (`G10`). */
const WHEN_IT_LAUNCHED = 1_770_000_000;

/** A machine this device could be holding. */
function aStackBehindTheLock(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/** The launch screen over a device that answers the unlock this way. */
function theScreenOnADeviceThat(ADeviceThatKnowsYou $device, Stack ...$paired): YourStacks
{
    $stacks = StacksInMemory::holding(...$paired);

    return new YourStacks(
        $stacks,
        AKeychainInMemory::working(),
        AShareSheetThatWasOffered::working(),
        VerdictsInMemory::working(),
        FrozenClock::at(Instant::atEpochSeconds(WHEN_IT_LAUNCHED)),
        new Opening($device, $stacks, ADeviceOnANetwork::connected()),
    );
}

/** What that screen puts on the glass. */
function whatTheLaunchDraws(YourStacks $screen): WhatTheDeviceWouldDraw
{
    return WhatTheDeviceWouldDraw::by($screen);
}

it('N4-R24 — a locked launch draws the reason and the way in, and nothing else', function (): void {
    $drawn = whatTheLaunchDraws(theScreenOnADeviceThat(
        ADeviceThatKnowsYou::refusing(),
        aStackBehindTheLock(),
    ));

    // Exactly, not *contains*. The requirement names three things that must not
    // be behind a lock — a first-run surface, an empty state and any frame
    // already rendered — and a check that merely found the unlock copy present
    // would pass for a frame carrying all three underneath it, which is the
    // failure this is written against: the machine list legible behind the
    // prompt.
    expect($drawn->said())->toBe([
        __('device.unlock_reason'),
        __('device.unlock'),
    ]);
});

it('N4-R24 — nothing behind a lock can be pressed', function (): void {
    $drawn = whatTheLaunchDraws(theScreenOnADeviceThat(
        ADeviceThatKnowsYou::refusing(),
        aStackBehindTheLock(),
    ));

    // The half that matters most. A sentence drawn behind a prompt leaks the
    // shape of somebody's household; a control drawn behind one can be pressed
    // through it, which is the lock failing at the thing it is for.
    expect($drawn->offers())->toBe([__('device.unlock')]);
});

it('N4-R24 — the name of a machine is not behind the lock', function (): void {
    $stack = aStackBehindTheLock();
    $drawn = whatTheLaunchDraws(theScreenOnADeviceThat(ADeviceThatKnowsYou::refusing(), $stack));

    // Named separately from the assertion above, which would catch it, because
    // this is the one a reader wants to see stated: the name is
    // what the operator chose, and the names of the machines in somebody's
    // house is exactly what an unlocked phone on a table would show a guest.
    expect($drawn->said())->not->toContain($stack->name()->shown());
});

it('N4-R22 — a device holding nothing reaches its first run with no prompt', function (): void {
    $device = ADeviceThatKnowsYou::refusing();

    $drawn = whatTheLaunchDraws(theScreenOnADeviceThat($device));

    // The device would refuse, and is never asked. A lock over an empty store
    // protects nothing, and a prompt protecting nothing is how somebody learns
    // the prompt is noise — which costs the lock its meaning on the launch
    // where it is real.
    expect($drawn->said())->not->toContain(__('device.unlock_reason'))
        ->and($drawn->said())->toContain(__('onboarding.what_this_is'));
});

it('N4-R23 — the store decides, so a pairing engages the lock without a flag', function (): void {
    $refusing = ADeviceThatKnowsYou::refusing();

    // The same device and the same screen class, differing only in what the
    // store holds. Nothing in between is set, cleared or remembered:
    // asks for the store itself rather than a flag the app maintains, and a
    // flag is exactly what would let these two frames come out the same.
    expect(whatTheLaunchDraws(theScreenOnADeviceThat($refusing))->said())
        ->not->toContain(__('device.unlock_reason'));

    expect(whatTheLaunchDraws(theScreenOnADeviceThat($refusing, aStackBehindTheLock()))->said())
        ->toContain(__('device.unlock_reason'));
});

it('N4-R3, N4-R22 — a handset with no screen lock set is not held shut', function (): void {
    $drawn = whatTheLaunchDraws(theScreenOnADeviceThat(
        ADeviceThatKnowsYou::withNoScreenLock(),
        aStackBehindTheLock(),
    ));

    // Refusing to open here would be this app requiring something the platform
    // does not have, on a device where the operator has already decided. The
    // machine is drawn, which is the working alternative that is asked for.
    expect($drawn->said())->toContain(aStackBehindTheLock()->name()->shown());
});
