<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function expect;
use function it;

use Modules\Connection\Api\HowThePairingWent;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;

it('opens having neither paired nor refused', function (): void {
    $notYet = HowThePairingWent::NotYet;

    expect($notYet->isPaired())->toBeFalse()
        ->and($notYet->hasNowhereToWriteItDown())->toBeFalse()
        ->and($notYet->couldNotOpenTheStore())->toBeFalse();
});

it('says a stack is paired only when it was written down', function (): void {
    // A pairing that could not be written down has not happened: the operator
    // would find nothing on the next launch.
    expect(HowThePairingWent::Paired->isPaired())->toBeTrue();
});

it('tells a device with no store apart from a store that would not open', function (): void {
    // N1-R10's habit applied to storage. One of the two is something the
    // operator can fix by trying again and the other is not, so one sentence
    // for both is the sentence that is unhelpful for whichever they are in.
    expect(HowThePairingWent::refused(WhyAStackCannotBeRemembered::DeviceHasNoSecureStorage))
        ->toBe(HowThePairingWent::NoStoreOnThisDevice)
        ->and(HowThePairingWent::refused(WhyAStackCannotBeRemembered::StoreWouldNotOpen))
        ->toBe(HowThePairingWent::TheStoreWouldNotOpen);
});

it('answers each refusal with a sentence of its own', function (): void {
    expect(HowThePairingWent::NoStoreOnThisDevice->hasNowhereToWriteItDown())->toBeTrue()
        ->and(HowThePairingWent::NoStoreOnThisDevice->couldNotOpenTheStore())->toBeFalse()
        ->and(HowThePairingWent::TheStoreWouldNotOpen->couldNotOpenTheStore())->toBeTrue()
        ->and(HowThePairingWent::TheStoreWouldNotOpen->hasNowhereToWriteItDown())->toBeFalse()
        ->and(HowThePairingWent::NoStoreOnThisDevice->isPaired())->toBeFalse();
});
