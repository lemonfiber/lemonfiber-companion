<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function expect;
use function it;

use Modules\Connection\Api\Opening;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\ADeviceOnANetwork;
use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\StacksInMemory;

/** A machine this device has been introduced to. Named for this file (`G10`). */
function aPairedMachine(string $seed = 'a'): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

/** Which of the four the launch came to, as a word. Named for this file (`G10`). */
function howItOpened(Opening $opening): string
{
    return $opening->found()->either(
        locked: static fn(): Code => Code::of('locked'),
        unpaired: static fn(): Code => Code::of('unpaired'),
        blocked: static fn(Obstacle $why): Code => Code::of(sprintf('blocked-%s', $why->value)),
        ready: static fn(StackId $stack): Code => Code::of(sprintf('ready-%s', $stack->stored())),
    )->shown();
}

it('N4-R19 — a device that will not open holds the app shut', function (): void {
    // Asked before anything touches a network. An app that reached a stack and
    // then asked for a passcode has already sent the credential it was holding,
    // so the check would be theatre over a request that already happened.
    $stacks = StacksInMemory::holding(aPairedMachine());

    expect(howItOpened(new Opening(ADeviceThatKnowsYou::refusing(), $stacks, ADeviceOnANetwork::connected())))->toBe('locked');
});

it('N4-R19 — nothing is asked of the stacks while the app is shut', function (): void {
    // The order is the requirement rather than a convenience, and this is the
    // assertion that pins it: a launch that read the stack list first would
    // pass the test above and still be wrong.
    $stacks = StacksInMemory::holding(aPairedMachine());

    new Opening(ADeviceThatKnowsYou::refusing(), $stacks, ADeviceOnANetwork::connected())->found();

    expect($stacks->timesAsked())->toBe(0);
});

it('N4-R22 — a device holding nothing is not asked to authenticate', function (): void {
    // The first launch of a freshly installed app. The device would refuse if
    // asked, and the point is that it is not asked: a lock over an empty store
    // stands in front of a screen that says there are no stacks yet, and an
    // authentication protecting nothing is how somebody learns to turn it off.
    expect(howItOpened(new Opening(
        ADeviceThatKnowsYou::refusing(),
        StacksInMemory::working(),
        ADeviceOnANetwork::connected(),
    )))->toBe('unpaired');
});

it('N4-R22 — a device holding a pairing is asked, on the same refusal', function (): void {
    // The counterfactual, and the half that keeps the carve-out from becoming
    // the rule: the only difference from the case above is that the store holds
    // something, and that is what puts the question.
    expect(howItOpened(new Opening(
        ADeviceThatKnowsYou::refusing(),
        StacksInMemory::holding(aPairedMachine()),
        ADeviceOnANetwork::connected(),
    )))->toBe('locked');
});

it('N1-R35 — a device with no stack opens unpaired, which is not a fault', function (): void {
    // A first run rather than a failure to reach. Reporting it as an obstacle
    // would be the app describing its own first launch as broken.
    expect(howItOpened(new Opening(ADeviceThatKnowsYou::willing(), StacksInMemory::working(), ADeviceOnANetwork::connected())))
        ->toBe('unpaired');
});

it('N1-R36 — a device with a stack opens ready, naming which', function (): void {
    $opening = new Opening(ADeviceThatKnowsYou::willing(), StacksInMemory::holding(aPairedMachine()), ADeviceOnANetwork::connected());

    expect(howItOpened($opening))->toBe(sprintf('ready-%s', str_repeat('a', Nonce::SHORTEST)));
});

it('N4-R3 — a device offering no authentication is not a locked one', function (): void {
    // A handset with no passcode set. Refusing to open would be this app
    // requiring something the platform does not have, on a device where the
    // operator has already decided — which is the shape `N4-R3` refuses one
    // permission at a time.
    $opening = new Opening(ADeviceThatKnowsYou::withNoScreenLock(), StacksInMemory::holding(aPairedMachine()), ADeviceOnANetwork::connected());

    expect(howItOpened($opening))->toBe(sprintf('ready-%s', str_repeat('a', Nonce::SHORTEST)));
});

it('N4-R3 — a device offering no authentication is never asked to unlock', function (): void {
    $device = ADeviceThatKnowsYou::withNoScreenLock();

    new Opening($device, StacksInMemory::holding(aPairedMachine()), ADeviceOnANetwork::connected())->found();

    expect($device->asked())->toBe(0);
});

it('N1-R31 — opens on the first stack paired, rather than choosing between them', function (): void {
    // Not a choice made on the operator's behalf: two stacks are not
    // interchangeable, and an operator with several meets the list. This is
    // only the answer to which one a launch that goes straight to one is about.
    $opening = new Opening(
        ADeviceThatKnowsYou::willing(),
        StacksInMemory::holding(aPairedMachine('a'), aPairedMachine('b')),
        ADeviceOnANetwork::connected(),
    );

    expect(howItOpened($opening))->toBe(sprintf('ready-%s', str_repeat('a', Nonce::SHORTEST)));
});

it('N1-R37 — a paired device with no network opens blocked, naming the network', function (): void {
    // The whole of why the port exists. Before it, a phone in flight mode and a
    // machine that is switched off produced the same silence at the socket and
    // the app reported both as the machine — sending somebody to a cupboard to
    // check a stack that was fine.
    $opening = new Opening(
        ADeviceThatKnowsYou::willing(),
        StacksInMemory::holding(aPairedMachine()),
        ADeviceOnANetwork::withNothingToReachOver(),
    );

    expect(howItOpened($opening))->toBe(sprintf('blocked-%s', Obstacle::DeviceHasNoNetwork->value));
});

it('N1-R37 — a launch that is held shut never asks about the network', function (): void {
    // `N4-R19` again, one question further along: the lock is answered before
    // anything touches a network, and a launch that asked the radio first would
    // satisfy every assertion about what it *said* and still be wrong.
    $network = ADeviceOnANetwork::withNothingToReachOver();

    new Opening(ADeviceThatKnowsYou::refusing(), StacksInMemory::holding(aPairedMachine()), $network)->found();

    expect($network->timesAsked())->toBe(0);
});

it('N1-R35 — a first run is never asked about the network either', function (): void {
    // A device with no stack has nothing to reach, so the question has no
    // answer worth having. Told somebody their wifi was off, it would be
    // answering a question they have not asked yet — they have not paired a
    // machine, and that is the screen they need.
    $network = ADeviceOnANetwork::withNothingToReachOver();

    new Opening(ADeviceThatKnowsYou::willing(), StacksInMemory::working(), $network)->found();

    expect($network->timesAsked())->toBe(0);
});

it('N1-R37 — a connected device is asked once and then left alone', function (): void {
    // A launch is not a poller (`N1-R66`). Asking twice is how a cheap question
    // becomes a habit, and the answer can change between the two — which would
    // make a launch that reported *ready* about a device that had since left
    // the network.
    $network = ADeviceOnANetwork::connected();

    new Opening(ADeviceThatKnowsYou::willing(), StacksInMemory::holding(aPairedMachine()), $network)->found();

    expect($network->timesAsked())->toBe(1);
});
