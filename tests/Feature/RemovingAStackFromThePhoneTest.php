<?php

declare(strict_types=1);

use Bootstrap\Composition\EveryKeeperOfAStack;
use Modules\Connection\Api\RemovingAStack;
use Modules\Connection\Api\WhatBecameOfRemoving;
use Modules\Vault\Api\PlatformStacks;
use Tests\Support\APhoneHoldingTwoStacks;
use Tests\Support\Fakes\RemovalsUnderWayInMemory;
use Tests\Support\Fakes\StacksInMemory;

// Remove from phone, over the stores the phone keeps a stack in: the secure
// store behind the pairing, the session and the markers, and the stores of
// readings. A removal is one act however many steps it takes, so it is cut
// after every step, finished as the app next opens, and never leaves the
// stack in a list in between.

it('lets go of the stack\'s pairing, session, readings and markers in one act, and of nothing of any other', function (): void {
    $phone = new APhoneHoldingTwoStacks();

    expect($phone->removing()->remove(APhoneHoldingTwoStacks::aStack('a')->id()))->toBe(WhatBecameOfRemoving::Removed)
        ->and($phone->stillKeeping(APhoneHoldingTwoStacks::aStack('a')->id()))->toBe([])
        ->and($phone->stillKeeping(APhoneHoldingTwoStacks::aStack('b')->id()))->toBe([0, 1, 2, 3, 4, 5])
        ->and(new PlatformStacks($phone->store)->underWay()->isEmpty())->toBeTrue()
        ->and($phone->stacks->configured()->knows(APhoneHoldingTwoStacks::aStack('b')->id()))->toBeTrue();
});

it('finishes a removal cut short after any step when the app next opens, and never lists the stack in between', function (int $stepsTaken): void {
    $phone = new APhoneHoldingTwoStacks();
    $removed = APhoneHoldingTwoStacks::aStack('a')->id();

    // The removal as far as it got before the app was stopped: written down,
    // and this many keepers asked.
    new PlatformStacks($phone->store)->begin($removed);

    foreach (array_slice($phone->keepers, 0, $stepsTaken) as $keeper) {
        $keeper->forgetTheStack($removed);
    }

    $listedWhileCut = $phone->stacks->configured()->knows($removed);
    $left = $phone->removing()->finishWhatWasLeft();

    expect($listedWhileCut)->toBeFalse()
        ->and($left->isEmpty())->toBeTrue()
        ->and($phone->stillKeeping($removed))->toBe([])
        ->and(new PlatformStacks($phone->store)->underWay()->isEmpty())->toBeTrue()
        ->and($phone->stillKeeping(APhoneHoldingTwoStacks::aStack('b')->id()))->toBe([0, 1, 2, 3, 4, 5]);
})->with([
    'before anything was let go of' => [0],
    'after the pairing' => [1],
    'after the session' => [2],
    'after the readings' => [3],
    'after the reading of what is up to date' => [4],
    'after the words' => [5],
    'after the work left running, before it was struck off' => [6],
]);

it('touches nothing where the removal cannot be written down', function (): void {
    $phone = new APhoneHoldingTwoStacks();
    $removing = new RemovingAStack(RemovalsUnderWayInMemory::refusing(), new EveryKeeperOfAStack(...$phone->keepers));

    expect($removing->remove(APhoneHoldingTwoStacks::aStack('a')->id()))->toBe(WhatBecameOfRemoving::Refused)
        ->and($phone->stillKeeping(APhoneHoldingTwoStacks::aStack('a')->id()))->toBe([0, 1, 2, 3, 4, 5])
        ->and($phone->stacks->configured()->knows(APhoneHoldingTwoStacks::aStack('a')->id()))->toBeTrue();
});

it('keeps a removal a keeper would not finish, as it happens and at the next opening, and finishes it once the keeper lets go', function (): void {
    $removed = APhoneHoldingTwoStacks::aStack('a');
    $holdingOn = StacksInMemory::holding($removed)->thenHoldingOn();
    $journal = RemovalsUnderWayInMemory::working();

    $went = new RemovingAStack($journal, new EveryKeeperOfAStack($holdingOn))->remove($removed->id());
    $still = new RemovingAStack($journal, new EveryKeeperOfAStack($holdingOn))->finishWhatWasLeft();
    $letsGo = StacksInMemory::holding($removed);
    $left = new RemovingAStack($journal, new EveryKeeperOfAStack($letsGo))->finishWhatWasLeft();

    expect($went)->toBe(WhatBecameOfRemoving::Finishing)
        ->and($still->holds($removed->id()))->toBeTrue()
        ->and($holdingOn->keepsAnythingOf($removed->id()))->toBeTrue()
        ->and($left->isEmpty())->toBeTrue()
        ->and($letsGo->keepsAnythingOf($removed->id()))->toBeFalse()
        ->and($journal->underWay()->isEmpty())->toBeTrue();
});

it('takes a stack back where it is paired again before its removal finished', function (): void {
    $phone = new APhoneHoldingTwoStacks();
    $removed = APhoneHoldingTwoStacks::aStack('a');

    new PlatformStacks($phone->store)->begin($removed->id());
    $phone->stacks->remember($removed);

    expect($phone->stacks->configured()->knows($removed->id()))->toBeTrue()
        ->and(new PlatformStacks($phone->store)->underWay()->isEmpty())->toBeTrue();
});
