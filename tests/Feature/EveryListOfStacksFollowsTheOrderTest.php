<?php

declare(strict_types=1);

use Modules\Connection\Api\Opening;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Operator\Internal\Screens\WhatTheWordsMean;
use Modules\Operator\Internal\Screens\YourStacks;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\ADeviceOnANetwork;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\AStackThatExplainsItsWords;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\StandingsInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatThePhoneKeeps;

// The order the operator puts the stacks in, followed by every list of them.

/** When the lists are drawn. */
const WHEN_THE_LISTS_ARE_DRAWN = 1_770_000_000;

/** A stack on the phone, named for this file. */
function aStackInTheOrder(string $called, string $seed): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of($called),
        Address::of('https://192.168.1.42'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

/** Three stacks, paired loft first, then the attic, then the shed, and put in the order shed, loft, attic. */
function threeStacksPutInOrder(): StacksInMemory
{
    $stacks = StacksInMemory::holding(
        aStackInTheOrder('The loft', 'a'),
        aStackInTheOrder('The attic', 'b'),
        aStackInTheOrder('The shed', 'c'),
    );
    $stacks->putInOrder(aStackInTheOrder('The shed', 'c')->id(), aStackInTheOrder('The loft', 'a')->id());

    return $stacks;
}

/**
 * The names a drawing offers, of these stacks only, in the order it offers them.
 *
 * @param list<string> $offered
 *
 * @return list<string>
 */
function theStacksAmong(array $offered): array
{
    return array_values(array_filter(
        $offered,
        static fn(string $one): bool => in_array($one, ['The loft', 'The attic', 'The shed'], strict: true),
    ));
}

it('lists the stacks on the phone in the order they were put in', function (): void {
    $stacks = threeStacksPutInOrder();
    $screen = new YourStacks(
        $stacks,
        AKeychainInMemory::working(),
        AShareSheetThatWasOffered::working(),
        StandingsInMemory::working(),
        FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_LISTS_ARE_DRAWN)),
        new Opening($stacks, ADeviceOnANetwork::connected()),
        WhatThePhoneKeeps::nothingToClear(),
        WhatThePhoneKeeps::nothingYet(),
        AStackThatSpeaksUp::holdingOpen(),
        ACaptureInMemory::inFront(),
        WhatThePhoneKeeps::nothingToFinish(),
    );

    expect(theStacksAmong(WhatTheDeviceWouldDraw::by($screen)->said()))->toBe(['The shed', 'The loft', 'The attic']);
});

it('offers the stacks to switch to in the order they were put in', function (): void {
    $screen = new WhatTheWordsMean(
        AStackThatExplainsItsWords::met(Obstacle::DeviceHasNoNetwork),
        AKeychainInMemory::working(),
        AroundThePhone::holding(threeStacksPutInOrder()),
    );
    $screen->setParams(['stack' => aStackInTheOrder('The loft', 'a')->id()->stored()]);
    $screen->chooseAStack();

    // The bar's title first, the stack on view, and then the list.
    expect(theStacksAmong(WhatTheDeviceWouldDraw::inTheListOfStacks($screen)->offers()))
        ->toBe(['The loft', 'The shed', 'The loft', 'The attic']);
});
