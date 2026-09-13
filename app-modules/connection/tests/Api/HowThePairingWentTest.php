<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function array_unique;
use function count;
use function expect;
use function it;

use Modules\Connection\Api\HowThePairingWent;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;

it('opens having neither paired nor refused', function (): void {
    $notYet = HowThePairingWent::NotYet;

    expect($notYet->isPaired())->toBeFalse()
        ->and($notYet->isNotYet())->toBeTrue();
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
    // Two refusals, two pairs of keys, and no two the same — which is what the
    // distinction above is *for*. Asserted as the keys rather than as booleans
    // because the keys are what a screen shows: a pair of accessors returning
    // true and false proves the cases differ and not that they say anything
    // different to the operator.
    $keys = [];

    foreach (HowThePairingWent::cases() as $went) {
        if ($went->isNotYet()) {
            continue;
        }

        $keys[] = $went->said();
        $keys[] = $went->remedy();
    }

    expect($keys)->toHaveCount(count(array_unique($keys)))
        ->and(HowThePairingWent::NoStoreOnThisDevice->said())
        ->not->toBe(HowThePairingWent::TheStoreWouldNotOpen->said())
        ->and(HowThePairingWent::NoStoreOnThisDevice->isPaired())->toBeFalse();
});
