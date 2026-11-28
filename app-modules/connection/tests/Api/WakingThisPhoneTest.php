<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function expect;
use function it;

use Modules\Connection\Api\WhatTurningWakingOnCameTo;
use Modules\Connection\Api\WhyWakingStayedOff;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADistributor;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\APushName;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AWakeFingerprint;
use Modules\Kernel\Api\AWakeRegistered;
use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowThisPhoneIsWoken;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhyNoEndpoint;

use function sprintf;
use function str_repeat;

use Tests\Support\AWakingPhone;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\AStackThatKeepsWakes;
use Tests\Support\Fakes\DistributorsOnAPhone;
use Tests\Support\Fakes\NotifyingKeptInMemory;
use Tests\Support\Fakes\PhoneKeysNamesKeptInMemory;
use Tests\Support\Fakes\WakingKeptInMemory;
use Tests\Support\TheWordCarriedOut;

function theLoftWakingThePhone(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('l', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

function aSessionOnTheLoft(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** The names this phone's key carries, one on the loft. */
function aKeyNamedOnTheLoft(): PhoneKeysNamesKeptInMemory
{
    $names = PhoneKeysNamesKeptInMemory::working();
    $names->keepTheName(theLoftWakingThePhone()->id(), APhoneKeysName::of('Galaxy A51 · 7f3a'));

    return $names;
}

/** What turning waking on came to, as one word. */
function turnedOn(WhatTurningWakingOnCameTo $came): string
{
    return $came->either(
        on: static fn(ADistributor $through): TheWordCarriedOut => new TheWordCarriedOut(sprintf('on through %s', $through->called())),
        off: static fn(WhyWakingStayedOff $why): TheWordCarriedOut => $why->either(
            noKeyName: static fn(): TheWordCarriedOut => new TheWordCarriedOut('no key name'),
            noEndpoint: static fn(WhyNoEndpoint $no): TheWordCarriedOut => new TheWordCarriedOut(sprintf('no endpoint: %s', $no->value)),
            refused: static fn(ARefusalInItsWords $said): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s', $said->summary())),
            met: static fn(Obstacle $met): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met: %s', $met->kind()->value)),
            notKept: static fn(): TheWordCarriedOut => new TheWordCarriedOut('not kept'),
        ),
    )->said;
}

/** How the phone keeps a wake registered at this address. */
function keptAt(string $address): string
{
    return sprintf('on %s', AWakeFingerprint::ofTheAddress($address)->shown());
}

/** How the stack wakes the phone, as one word. */
function wokenNow(HowThisPhoneIsWoken $woken): string
{
    return $woken->either(
        off: static fn(): TheWordCarriedOut => new TheWordCarriedOut('off'),
        on: static fn(ADistributor $through, AWakeRegistered $wake): TheWordCarriedOut => new TheWordCarriedOut(sprintf('on %s', $wake->fingerprint->shown())),
        owed: static fn(APushName $name): TheWordCarriedOut => new TheWordCarriedOut(sprintf('owed %s', $name->shown())),
    )->said;
}

it('registers the distributor\'s endpoint with the stack under the name this phone\'s key carries there, and keeps it', function (): void {
    $phone = DistributorsOnAPhone::with();
    $stack = AStackThatKeepsWakes::reachable();
    $waking = AWakingPhone::waking($phone, $stack, aKeyNamedOnTheLoft());

    expect(turnedOn($waking->turnOn(theLoftWakingThePhone(), aSessionOnTheLoft(), DistributorsOnAPhone::ntfy())))->toBe('on through ntfy')
        ->and($stack->holds(theLoftWakingThePhone(), 'galaxy-a51-7f3a'))->toStartWith(DistributorsOnAPhone::PUSHED_AT)
        ->and(wokenNow($waking->forTheStack(theLoftWakingThePhone()->id())))->toBe(keptAt($stack->holds(theLoftWakingThePhone(), 'galaxy-a51-7f3a')))
        ->and($phone->holdsARegistrationFor(theLoftWakingThePhone()->id()))->toBeTrue();
});

it('stays off, leaving nothing behind, where the phone has no key on the stack, no endpoint is given, or the stack refuses or is not reached', function (DistributorsOnAPhone $phone, AStackThatKeepsWakes $stack, bool $named, string $said): void {
    $waking = AWakingPhone::waking($phone, $stack, $named ? aKeyNamedOnTheLoft() : PhoneKeysNamesKeptInMemory::working());

    expect(turnedOn($waking->turnOn(theLoftWakingThePhone(), aSessionOnTheLoft(), DistributorsOnAPhone::ntfy())))->toBe($said)
        ->and(wokenNow($waking->forTheStack(theLoftWakingThePhone()->id())))->toBe('off')
        ->and($phone->holdsARegistrationFor(theLoftWakingThePhone()->id()))->toBeFalse();
})->with([
    'no key on the stack' => [DistributorsOnAPhone::with(), AStackThatKeepsWakes::reachable(), false, 'no key name'],
    'no endpoint given' => [DistributorsOnAPhone::givingNone(WhyNoEndpoint::NoAnswer), AStackThatKeepsWakes::reachable(), true, 'no endpoint: no_answer'],
    'an address the stack refuses' => [DistributorsOnAPhone::with(), AStackThatKeepsWakes::refusingTheAddress(), true, sprintf('refused: %s', AStackThatKeepsWakes::NOT_HTTPS)],
    'a stack not reached' => [DistributorsOnAPhone::with(), AStackThatKeepsWakes::unreachable(), true, sprintf('met: %s', KindOfObstacle::StackDidNotAnswer->value)],
]);

it('asks the stack to forget the endpoint again where the phone cannot keep that it holds it', function (): void {
    $stack = AStackThatKeepsWakes::reachable();
    $waking = AWakingPhone::waking(DistributorsOnAPhone::with(), $stack, aKeyNamedOnTheLoft(), WakingKeptInMemory::unreachable());

    expect(turnedOn($waking->turnOn(theLoftWakingThePhone(), aSessionOnTheLoft(), DistributorsOnAPhone::ntfy())))->toBe('not kept')
        ->and($stack->holds(theLoftWakingThePhone(), 'galaxy-a51-7f3a'))->toBe('');
});

it('stops at once when turned off, telling the stack, or owing it the removal until it is next read', function (): void {
    $phone = DistributorsOnAPhone::with();
    $stack = AStackThatKeepsWakes::reachable();
    $waking = AWakingPhone::waking($phone, $stack, aKeyNamedOnTheLoft());
    $waking->turnOn(theLoftWakingThePhone(), aSessionOnTheLoft(), DistributorsOnAPhone::ntfy());

    $stack->goesAway();

    expect(wokenNow($waking->turnOff(theLoftWakingThePhone(), aSessionOnTheLoft())))->toBe('owed galaxy-a51-7f3a')
        ->and($phone->holdsARegistrationFor(theLoftWakingThePhone()->id()))->toBeFalse()
        ->and(wokenNow($waking->sendWhatIsOwed(theLoftWakingThePhone(), aSessionOnTheLoft())))->toBe('owed galaxy-a51-7f3a')
        ->and(wokenNow($waking->turnOff(theLoftWakingThePhone(), aSessionOnTheLoft())))->toBe('owed galaxy-a51-7f3a');

    $stack->comesBack();

    expect(wokenNow($waking->sendWhatIsOwed(theLoftWakingThePhone(), aSessionOnTheLoft())))->toBe('off')
        ->and($stack->holds(theLoftWakingThePhone(), 'galaxy-a51-7f3a'))->toBe('')
        ->and($waking->keepsAnythingOf(theLoftWakingThePhone()->id()))->toBeFalse()
        ->and(wokenNow($waking->turnOff(theLoftWakingThePhone(), aSessionOnTheLoft())))->toBe('off')
        ->and(wokenNow($waking->sendWhatIsOwed(theLoftWakingThePhone(), aSessionOnTheLoft())))->toBe('off');
});

it('sends nothing to a stack that wakes the phone when it is read again', function (): void {
    $stack = AStackThatKeepsWakes::reachable();
    $waking = AWakingPhone::waking(DistributorsOnAPhone::with(), $stack, aKeyNamedOnTheLoft());
    $waking->turnOn(theLoftWakingThePhone(), aSessionOnTheLoft(), DistributorsOnAPhone::ntfy());
    $asked = $stack->timesAsked();

    expect(wokenNow($waking->sendWhatIsOwed(theLoftWakingThePhone(), aSessionOnTheLoft())))->toStartWith('on ')
        ->and($stack->timesAsked())->toBe($asked);
});

it('sends a removal still owed before turning waking on again', function (): void {
    $stack = AStackThatKeepsWakes::reachable();
    $waking = AWakingPhone::waking(DistributorsOnAPhone::with(), $stack, aKeyNamedOnTheLoft());
    $waking->turnOn(theLoftWakingThePhone(), aSessionOnTheLoft(), DistributorsOnAPhone::ntfy());
    $stack->goesAway();
    $waking->turnOff(theLoftWakingThePhone(), aSessionOnTheLoft());
    $stack->comesBack();

    expect(turnedOn($waking->turnOn(theLoftWakingThePhone(), aSessionOnTheLoft(), DistributorsOnAPhone::ntfy())))->toBe('on through ntfy');
});

it('follows the distributor: a changed endpoint is registered in place of the old, and a dropped one turns waking off', function (): void {
    $phone = DistributorsOnAPhone::with();
    $stack = AStackThatKeepsWakes::reachable();
    $waking = AWakingPhone::waking($phone, $stack, aKeyNamedOnTheLoft());
    $waking->turnOn(theLoftWakingThePhone(), aSessionOnTheLoft(), DistributorsOnAPhone::ntfy());
    $held = $stack->holds(theLoftWakingThePhone(), 'galaxy-a51-7f3a');

    expect(wokenNow($waking->followTheDistributor(theLoftWakingThePhone(), aSessionOnTheLoft())))->toBe(keptAt($held));

    $phone->movesTheEndpointOf(theLoftWakingThePhone()->id(), 'https://ntfy.sh/upMoved?up=1');

    expect(wokenNow($waking->followTheDistributor(theLoftWakingThePhone(), aSessionOnTheLoft())))->toBe(keptAt('https://ntfy.sh/upMoved?up=1'))
        ->and($stack->holds(theLoftWakingThePhone(), 'galaxy-a51-7f3a'))->toBe('https://ntfy.sh/upMoved?up=1');

    $phone->letGo(theLoftWakingThePhone()->id());

    expect(wokenNow($waking->followTheDistributor(theLoftWakingThePhone(), aSessionOnTheLoft())))->toBe('off')
        ->and($stack->holds(theLoftWakingThePhone(), 'galaxy-a51-7f3a'))->toBe('')
        ->and(wokenNow($waking->followTheDistributor(theLoftWakingThePhone(), aSessionOnTheLoft())))->toBe('off');
});

it('keeps the old endpoint while the stack cannot be told of a new one', function (): void {
    $phone = DistributorsOnAPhone::with();
    $stack = AStackThatKeepsWakes::reachable();
    $waking = AWakingPhone::waking($phone, $stack, aKeyNamedOnTheLoft());
    $waking->turnOn(theLoftWakingThePhone(), aSessionOnTheLoft(), DistributorsOnAPhone::ntfy());
    $held = $stack->holds(theLoftWakingThePhone(), 'galaxy-a51-7f3a');
    $phone->movesTheEndpointOf(theLoftWakingThePhone()->id(), 'https://ntfy.sh/upMoved?up=1');
    $stack->goesAway();

    expect(wokenNow($waking->followTheDistributor(theLoftWakingThePhone(), aSessionOnTheLoft())))->toBe(keptAt($held));

    $waking->turnOff(theLoftWakingThePhone(), aSessionOnTheLoft());

    expect(wokenNow($waking->followTheDistributor(theLoftWakingThePhone(), aSessionOnTheLoft())))->toBe('owed galaxy-a51-7f3a');
});

it('turns waking off where a changed endpoint is refused by the stack', function (): void {
    $phone = DistributorsOnAPhone::with();
    $kept = WakingKeptInMemory::empty();
    AWakingPhone::waking($phone, AStackThatKeepsWakes::reachable(), aKeyNamedOnTheLoft(), $kept)->turnOn(theLoftWakingThePhone(), aSessionOnTheLoft(), DistributorsOnAPhone::ntfy());
    $phone->movesTheEndpointOf(theLoftWakingThePhone()->id(), 'https://ntfy.sh/upMoved?up=1');
    $refusing = AWakingPhone::waking($phone, AStackThatKeepsWakes::refusingTheAddress(), aKeyNamedOnTheLoft(), $kept);

    expect(wokenNow($refusing->followTheDistributor(theLoftWakingThePhone(), aSessionOnTheLoft())))->toBe('off');
});

it('tells the stack to forget the endpoint as it is removed from the phone, and says where it could not be told', function (): void {
    $stack = AStackThatKeepsWakes::reachable();
    $phone = DistributorsOnAPhone::with();
    $waking = AWakingPhone::waking($phone, $stack, aKeyNamedOnTheLoft());

    expect($waking->stillHeldAfterTelling(theLoftWakingThePhone(), aSessionOnTheLoft()))->toBeFalse();

    $waking->turnOn(theLoftWakingThePhone(), aSessionOnTheLoft(), DistributorsOnAPhone::ntfy());

    expect($phone->holdsARegistrationFor(theLoftWakingThePhone()->id()))->toBeTrue()
        ->and($waking->stillHeldAfterTelling(theLoftWakingThePhone(), aSessionOnTheLoft()))->toBeFalse()
        ->and($phone->holdsARegistrationFor(theLoftWakingThePhone()->id()))->toBeFalse()
        ->and($stack->holds(theLoftWakingThePhone(), 'galaxy-a51-7f3a'))->toBe('');

    $waking->turnOn(theLoftWakingThePhone(), aSessionOnTheLoft(), DistributorsOnAPhone::ntfy());
    $stack->goesAway();

    expect($waking->stillHeldAfterTelling(theLoftWakingThePhone(), aSessionOnTheLoft()))->toBeTrue()
        ->and($waking->stillHeldAfterTelling(theLoftWakingThePhone(), aSessionOnTheLoft()))->toBeTrue();
});

it('forgets the stack with its registration and key pair, and says whether anything is kept', function (): void {
    $phone = DistributorsOnAPhone::with();
    $waking = AWakingPhone::waking($phone, AStackThatKeepsWakes::reachable(), aKeyNamedOnTheLoft());
    $waking->turnOn(theLoftWakingThePhone(), aSessionOnTheLoft(), DistributorsOnAPhone::ntfy());

    expect($waking->keepsAnythingOf(theLoftWakingThePhone()->id()))->toBeTrue()
        ->and($waking->forgetTheStack(theLoftWakingThePhone()->id())->howMany())->toBe(1)
        ->and($waking->keepsAnythingOf(theLoftWakingThePhone()->id()))->toBeFalse()
        ->and($phone->holdsARegistrationFor(theLoftWakingThePhone()->id()))->toBeFalse()
        ->and(AWakingPhone::waking(seal: ASealInMemory::withNoSecureStorage())->keepsAnythingOf(theLoftWakingThePhone()->id()))->toBeTrue();
});

it('clears alongside every store, dropping every registration, and owes each stack that held the endpoint its removal', function (): void {
    $phone = DistributorsOnAPhone::with();
    $stack = AStackThatKeepsWakes::reachable();
    $kept = WakingKeptInMemory::empty();
    $waking = AWakingPhone::waking($phone, $stack, aKeyNamedOnTheLoft(), $kept);
    $waking->turnOn(theLoftWakingThePhone(), aSessionOnTheLoft(), DistributorsOnAPhone::ntfy());
    $notifying = NotifyingKeptInMemory::empty();

    $cleared = $waking->clearedAlongside($kept, Configured::of(theLoftWakingThePhone()));

    expect($cleared->howMany())->toBe(1)
        ->and($phone->everyLetGoCount())->toBe(1)
        ->and(wokenNow($waking->forTheStack(theLoftWakingThePhone()->id())))->toBe('owed galaxy-a51-7f3a')
        ->and($waking->clearedAlongside($notifying, Configured::of(theLoftWakingThePhone()))->howMany())->toBe(0)
        ->and(wokenNow($waking->forTheStack(theLoftWakingThePhone()->id())))->toBe('owed galaxy-a51-7f3a');
});

it('reads waking it cannot open as off', function (): void {
    $seal = ASealInMemory::working();
    $kept = WakingKeptInMemory::empty();
    $waking = AWakingPhone::waking(DistributorsOnAPhone::with(), AStackThatKeepsWakes::reachable(), aKeyNamedOnTheLoft(), $kept, $seal);
    $waking->turnOn(theLoftWakingThePhone(), aSessionOnTheLoft(), DistributorsOnAPhone::ntfy());

    expect(wokenNow(AWakingPhone::waking(kept: $kept, seal: $seal->losesItsKeys())->forTheStack(theLoftWakingThePhone()->id())))->toBe('off')
        ->and(wokenNow(AWakingPhone::waking(kept: $kept, seal: ASealInMemory::thatWillNotOpen())->forTheStack(theLoftWakingThePhone()->id())))->toBe('off');
});
