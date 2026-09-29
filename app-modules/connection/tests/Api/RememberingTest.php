<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Connection\Api\HowThePairingWent;
use Modules\Connection\Api\Remembering;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;

use function str_repeat;

use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\StacksInMemory;

/** The loft, as its material names it, presenting the certificate named. */
function theLoftPresenting(string $digest, string $at = 'https://192.168.1.42'): Stack
{
    return Stack::of(
        StackId::saidBy('7f3c9a1e5b2d4086a9c1e3f5b7d90246'),
        StackName::of('The loft'),
        Address::of($at),
        Fingerprint::of(str_repeat($digest, Fingerprint::CHARACTERS)),
    );
}

/** A keychain holding a session for the loft. */
function signedIntoTheLoft(): AKeychainInMemory
{
    $sessions = AKeychainInMemory::working();
    $sessions->keep(theLoftPresenting('a')->id(), Session::of('a-session-for-the-loft'), Whose::theOperator());

    return $sessions;
}

it('lets go of the session where a re-pairing changes the certificate', function (): void {
    $sessions = signedIntoTheLoft();
    $stacks = StacksInMemory::holding(theLoftPresenting('a'));

    $went = new Remembering($stacks, $sessions)->stack(theLoftPresenting('b'));

    expect($went)->toBe(HowThePairingWent::PairedAgainOnANewCertificate)
        ->and($sessions->isHolding(theLoftPresenting('a')->id()))->toBeFalse()
        ->and($stacks->configured()->stack(theLoftPresenting('a')->id())->presents()->is(theLoftPresenting('b')->presents()))->toBeTrue();
});

it('keeps the session where a re-pairing keeps the certificate, wherever the machine now answers', function (): void {
    $sessions = signedIntoTheLoft();
    $stacks = StacksInMemory::holding(theLoftPresenting('a'));

    $went = new Remembering($stacks, $sessions)->stack(theLoftPresenting('a', 'https://192.168.1.77'));

    expect($went)->toBe(HowThePairingWent::PairedAgain)
        ->and($sessions->isHolding(theLoftPresenting('a')->id()))->toBeTrue()
        ->and($stacks->configured()->stack(theLoftPresenting('a')->id())->at()->forTheClient())->toContain('192.168.1.77');
});

it('keeps the session where the new certificate could not be written down, since the old pin still stands', function (): void {
    $sessions = signedIntoTheLoft();
    $stacks = StacksInMemory::holding(theLoftPresenting('a'))->thenRefusing(WhyAStackCannotBeRemembered::StoreWouldNotOpen);

    $went = new Remembering($stacks, $sessions)->stack(theLoftPresenting('b'));

    expect($went)->toBe(HowThePairingWent::TheStoreWouldNotOpen)
        ->and($sessions->isHolding(theLoftPresenting('a')->id()))->toBeTrue();
});

it('pairs a machine for the first time without touching any session', function (): void {
    $sessions = signedIntoTheLoft();
    $stacks = StacksInMemory::working();

    $went = new Remembering($stacks, $sessions)->stack(theLoftPresenting('b'));

    expect($went)->toBe(HowThePairingWent::Paired)
        ->and($stacks->configured()->knows(theLoftPresenting('b')->id()))->toBeTrue()
        ->and($sessions->isHolding(theLoftPresenting('a')->id()))->toBeTrue();
});

it('says a machine already held was updated where the same certificate comes back', function (): void {
    $sessions = signedIntoTheLoft();
    $stacks = StacksInMemory::holding(theLoftPresenting('a'));

    $went = new Remembering($stacks, $sessions)->stack(theLoftPresenting('a'));

    expect($went)->toBe(HowThePairingWent::PairedAgain)
        ->and($sessions->isHolding(theLoftPresenting('a')->id()))->toBeTrue();
});

it('decides which machine from the identifier alone, whatever address and certificate come with it', function (): void {
    // Another machine answering at the loft's address with the loft's
    // certificate is still another machine: it is added, and the loft and its
    // session are left as they were.
    $sessions = signedIntoTheLoft();
    $stacks = StacksInMemory::holding(theLoftPresenting('a'));
    $theCupboard = Stack::of(
        StackId::saidBy('0a1b2c3d4e5f60718293a4b5c6d7e8f9'),
        StackName::of('The cupboard'),
        Address::of('https://192.168.1.42'),
        Fingerprint::of(str_repeat('a', Fingerprint::CHARACTERS)),
    );

    $went = new Remembering($stacks, $sessions)->stack($theCupboard);

    expect($went)->toBe(HowThePairingWent::Paired)
        ->and(iterator_to_array($stacks->configured(), preserve_keys: false))->toHaveCount(2)
        ->and($stacks->configured()->stack(theLoftPresenting('a')->id())->name()->shown())->toBe('The loft')
        ->and($sessions->isHolding(theLoftPresenting('a')->id()))->toBeTrue();
});

it('says a device with no store paired nothing, and leaves every session standing', function (): void {
    $sessions = signedIntoTheLoft();
    $stacks = StacksInMemory::holding(theLoftPresenting('a'))->thenRefusing(WhyAStackCannotBeRemembered::DeviceHasNoSecureStorage);

    $went = new Remembering($stacks, $sessions)->stack(theLoftPresenting('b'));

    expect($went)->toBe(HowThePairingWent::NoStoreOnThisDevice)
        ->and($sessions->isHolding(theLoftPresenting('a')->id()))->toBeTrue();
});
