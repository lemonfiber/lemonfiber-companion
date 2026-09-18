<?php

declare(strict_types=1);

use Modules\Dx\Providers\DxServiceProvider;
use Modules\Kernel\Api\Stacks;
use Modules\Operator\Internal\Screens\PairByScanning;
use Modules\Operator\Internal\Screens\YourStacks;

// The first run, walked through rather than only walked to.
//
// `N1-R54` builds the opening sequence a step at a time and ends it at pairing.
// `N1-R56` says a paired device never sees it again. `ADeviceAlreadyPaired`
// seeds three machines, which is what makes every other screen reachable — and
// what made this one reachable only with that affordance off, at which point
// there was nothing to pair with either.
//
// So the screen an operator meets first was the screen this module could not
// reach. A camera stand-in closes it: there is no camera on a laptop and no
// stack in front of the phone, and either alone makes the scanned road
// unwalkable.
//
// What runs is the real screen, the real reader and the real refusals. The
// material is written from `WhatPairingMaterialSays` — the enum `Pairing::read()`
// checks its keys against — so it cannot drift from the format, and
// `Pairing::read()` refuses it on its own terms or does not.

/** The stand-ins, put over the ports, the way the switch does it. */
function withNoCameraAndNoStack(): void
{
    config(['dx.stands_in' => true]);
    app()->register(new DxServiceProvider(app()), force: true);
}

/**
 * How many machines this device holds.
 *
 * Asked of the port, because that is what a screen asks — a count taken off
 * the stand-in that seeded them would be a second opinion about what the device
 * knows.
 */
function howManyMachinesAreHeld(): int
{
    $held = 0;

    foreach (app()->make(Stacks::class)->configured() as $stack) {
        $held++;
    }

    return $held;
}

/** The scanning screen, built the way the application builds one. */
function theScanningScreen(): PairByScanning
{
    return app()->make(PairByScanning::class);
}

/** The machines screen, built the same way, so its first-run answer is the app's. */
function theMachinesScreen(): YourStacks
{
    return app()->make(YourStacks::class);
}

it('N1-R6 — the camera sees a code for a machine this device has not met', function (): void {
    withNoCameraAndNoStack();

    $before = howManyMachinesAreHeld();
    $screen = theScanningScreen();

    // `N1-R11` wants a name and the material carries none, so the field is what
    // the control waits on — typed through the framework's own property sync,
    // which is how a character reaches a screen on a device.
    $screen->__syncProperty('called', 'The one in the cupboard');

    expect($screen->mayScan())->toBeTrue();

    $screen->scan();

    expect($screen->went()->isNotYet())
        ->toBeFalse('the camera saw nothing, so the first run ends where it begins');

    expect(howManyMachinesAreHeld())->toBe(
        $before + 1,
        'the code was read and the machine was not written down, so the sequence cannot be finished',
    );
});

it('N1-R56 — a device that has paired opens on its machines rather than the sequence', function (): void {
    withNoCameraAndNoStack();

    expect(theMachinesScreen()->theFirstRunIsStillRunning())->toBeFalse();
});
