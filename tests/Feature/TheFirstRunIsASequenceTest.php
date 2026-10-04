<?php

declare(strict_types=1);

use Modules\Connection\Api\Opening;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Permission;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Operator\Internal\Screens\YourStacks;
use Modules\Operator\Internal\WhereTheFirstRunIs;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ADeviceOnANetwork;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatThePhoneKeeps;

// The first run is a sequence, not a wall.
//
// A single frame satisfies the first-run case — it can say that setup happens at the
// machine — and still leave somebody who has just installed a companion app to
// work out from a paragraph why it is asking them to go and stand somewhere
// else. A heading, two sentences and three buttons at once says nothing about
// which of them to read first, and a control that skips the reading is what
// makes the reading optional.
//
// The three requirements are three separate claims and each is asked here
// separately: that there are steps and they arrive in an order, that
// a step says where it is and can be left, and that none of it
// exists for a device that already holds a pairing.

/** The moment the sequence is read at. Named for this file (`G10`). */
const WHEN_IT_WAS_FIRST_RUN = 1_770_000_000;

/** A stack this device could already know, for the cases where it does. */
function aStackAlreadyPaired(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('f', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** The launch screen over a device that holds exactly these stacks. */
function theScreenAFirstRunLandsOn(Stack ...$paired): YourStacks
{
    $stacks = StacksInMemory::holding(...$paired);

    return new YourStacks(
        $stacks,
        AKeychainInMemory::working(),
        AShareSheetThatWasOffered::working(),
        new Opening($stacks, ADeviceOnANetwork::connected()),
        WhatThePhoneKeeps::nothingToClear(),
        WhatThePhoneKeeps::nothingTooOld(),
        WhatThePhoneKeeps::nothingToFinish(),
        AroundThePhone::alreadyOpened(),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(clock: FrozenClock::at(Instant::atEpochSeconds(WHEN_IT_WAS_FIRST_RUN))),
    );
}

it('draws its first step on a device holding nothing, and no unlock', function (): void {
    // A lock over an empty store protects nothing, so the gate builds this
    // screen without asking; what reaches the glass is the first step.
    $drawn = WhatTheDeviceWouldDraw::by(theScreenAFirstRunLandsOn());

    expect($drawn->said())->toContain(__('onboarding.what_this_is'))
        ->and($drawn->said())->not->toContain(__('device.unlock_reason'));
});

it('a first run opens on what the app is, not on a button', function (): void {
    // The order is the requirement. A sequence that opened on pairing would be
    // the old screen with two extra frames nobody reaches.
    expect(theScreenAFirstRunLandsOn()->firstRunIsAt())->toBe(WhereTheFirstRunIs::WhatThisIs);
});

it('the steps arrive in the order the requirement names them', function (): void {
    $screen = theScreenAFirstRunLandsOn();
    $walked = [$screen->firstRunIsAt()];

    // Walked rather than asserted case by case, because what is asked for
    // is a path: what the app is, then that setup happens at the machine, then
    // pairing. Three separate assertions would pass for three steps that each
    // knew their own name and could not be reached from one another.
    foreach (WhereTheFirstRunIs::cases() as $ignored) {
        $screen->goOn();
        $walked[] = $screen->firstRunIsAt();
    }

    expect($walked)->toBe([
        WhereTheFirstRunIs::WhatThisIs,
        WhereTheFirstRunIs::AtTheMachine,
        WhereTheFirstRunIs::TheLocalNetwork,
        WhereTheFirstRunIs::Pairing,
        // One press past the end, which stays on it. The alternative is a step
        // that is nothing, and a screen drawing nothing is what an operator
        // reports as the app having crashed.
        WhereTheFirstRunIs::Pairing,
    ]);
});

it('every step says which it is and how many there are', function (): void {
    $seen = [];

    foreach (WhereTheFirstRunIs::cases() as $at) {
        $seen[] = [$at->step(), $at->ofHowMany()];
    }

    // Counted from one and against the cases, so a fourth step changes the
    // denominator on every step by existing. A total written down somewhere is
    // a total that reads "step 3 of 2" the day somebody adds one.
    expect($seen)->toBe([[1, 4], [2, 4], [3, 4], [4, 4]]);
});

it('leaving lands on the way into pairing rather than on nothing', function (): void {
    $screen = theScreenAFirstRunLandsOn();

    $screen->skipAhead();

    // The point of the requirement: somebody who skipped is not dropped on a
    // blank screen, they are put where the sequence was going — one step short
    // of pairing, on why the app reaches the local network, because pairing is
    // where the platform asks.
    expect($screen->firstRunIsAt())->toBe(WhereTheFirstRunIs::TheLocalNetwork);

    $screen->goOn();

    expect($screen->firstRunIsAt())->toBe(WhereTheFirstRunIs::Pairing)
        ->and($screen->pairingIsOffered())->toBeTrue();
});

it('says why the app reaches the local network, and what still works without it, before pairing', function (): void {
    expect([WhereTheFirstRunIs::TheLocalNetwork->said(), WhereTheFirstRunIs::TheLocalNetwork->explained()])
        ->toBe([Permission::LocalNetwork->reason(), Permission::LocalNetwork->alternative()])
        ->and(WhereTheFirstRunIs::TheLocalNetwork->andThen())->toBe(WhereTheFirstRunIs::Pairing);
});

it('pairing is offered at the end of the sequence and not before', function (): void {
    $screen = theScreenAFirstRunLandsOn();

    expect($screen->pairingIsOffered())->toBeFalse();

    $screen->goOn();

    expect($screen->pairingIsOffered())->toBeFalse();

    $screen->goOn();

    expect($screen->pairingIsOffered())->toBeFalse();

    $screen->goOn();

    // A `pair now` button under step one is the wall the sequence exists to refuse:
    // it makes the two sentences above it optional, which makes them unread.
    expect($screen->pairingIsOffered())->toBeTrue();
});

it('a device holding a pairing is offered pairing and never the sequence', function (): void {
    $screen = theScreenAFirstRunLandsOn(aStackAlreadyPaired());

    // Tied to the store rather than to a flag, which is why this holds without
    // anything having been reset: the sequence is drawn inside the arm for *no
    // stacks*, so a device with one never evaluates it. Adding a second machine
    // is what somebody with one is on this screen to do, so the controls stay.
    expect($screen->nothingIsPairedYet())->toBeFalse()
        ->and($screen->pairingIsOffered())->toBeTrue();
});

it('offers a way to leave early only before the step it would land on', function (): void {
    $leavable = [];

    foreach (WhereTheFirstRunIs::cases() as $at) {
        $leavable[$at->name] = $at->mayBeSkipped();
    }

    expect($leavable)->toBe(['WhatThisIs' => true, 'AtTheMachine' => true, 'TheLocalNetwork' => false, 'Pairing' => false]);
});
