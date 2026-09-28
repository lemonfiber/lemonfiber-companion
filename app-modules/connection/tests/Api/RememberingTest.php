<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function expect;
use function it;

use Modules\Connection\Api\Remembering;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Remembered;
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

/** Whether a pairing was written down, as a word a test can compare. */
function whetherItWasKept(Remembered $remembered): string
{
    return $remembered->either(
        remembered: static fn(): WhatBecameOfIt => new WhatBecameOfIt('remembered'),
        refused: static fn(WhyAStackCannotBeRemembered $why): WhatBecameOfIt => new WhatBecameOfIt($why->name),
    )->said;
}

/** One word carried out of an arm. */
final readonly class WhatBecameOfIt
{
    public function __construct(public string $said) {}
}

it('lets go of the session where a re-pairing changes the certificate', function (): void {
    $sessions = signedIntoTheLoft();
    $stacks = StacksInMemory::holding(theLoftPresenting('a'));

    $remembered = new Remembering($stacks, $sessions)->stack(theLoftPresenting('b'));

    expect(whetherItWasKept($remembered))->toBe('remembered')
        ->and($sessions->isHolding(theLoftPresenting('a')->id()))->toBeFalse()
        ->and($stacks->configured()->stack(theLoftPresenting('a')->id())->presents()->is(theLoftPresenting('b')->presents()))->toBeTrue();
});

it('keeps the session where a re-pairing keeps the certificate, wherever the machine now answers', function (): void {
    $sessions = signedIntoTheLoft();
    $stacks = StacksInMemory::holding(theLoftPresenting('a'));

    new Remembering($stacks, $sessions)->stack(theLoftPresenting('a', 'https://192.168.1.77'));

    expect($sessions->isHolding(theLoftPresenting('a')->id()))->toBeTrue()
        ->and($stacks->configured()->stack(theLoftPresenting('a')->id())->at()->forTheClient())->toContain('192.168.1.77');
});

it('keeps the session where the new certificate could not be written down, since the old pin still stands', function (): void {
    $sessions = signedIntoTheLoft();
    $stacks = StacksInMemory::holding(theLoftPresenting('a'))->thenRefusing(WhyAStackCannotBeRemembered::StoreWouldNotOpen);

    $remembered = new Remembering($stacks, $sessions)->stack(theLoftPresenting('b'));

    expect(whetherItWasKept($remembered))->toBe('StoreWouldNotOpen')
        ->and($sessions->isHolding(theLoftPresenting('a')->id()))->toBeTrue();
});

it('pairs a machine for the first time without touching any session', function (): void {
    $sessions = signedIntoTheLoft();
    $stacks = StacksInMemory::working();

    $remembered = new Remembering($stacks, $sessions)->stack(theLoftPresenting('b'));

    expect(whetherItWasKept($remembered))->toBe('remembered')
        ->and($stacks->configured()->knows(theLoftPresenting('b')->id()))->toBeTrue()
        ->and($sessions->isHolding(theLoftPresenting('a')->id()))->toBeTrue();
});
