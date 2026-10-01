<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function expect;
use function it;

use Modules\Connection\Api\Opening;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\ADeviceOnANetwork;
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

/** Which of the three the launch came to, as a word. Named for this file (`G10`). */
function howItOpened(Opening $opening): string
{
    return $opening->found()->either(
        unpaired: static fn(): Code => Code::of('unpaired'),
        blocked: static fn(Obstacle $why): Code => Code::of(sprintf('blocked-%s', $why->kind()->value)),
        ready: static fn(StackId $stack): Code => Code::of(sprintf('ready-%s', $stack->stored())),
    )->shown();
}

it('a device with no stack opens unpaired, which is not a fault', function (): void {
    // A first run rather than a failure to reach. Reporting it as an obstacle
    // would be the app describing its own first launch as broken.
    expect(howItOpened(new Opening(StacksInMemory::working(), ADeviceOnANetwork::connected())))
        ->toBe('unpaired');
});

it('a device with a stack opens ready, naming which', function (): void {
    $opening = new Opening(StacksInMemory::holding(aPairedMachine()), ADeviceOnANetwork::connected());

    expect(howItOpened($opening))->toBe(sprintf('ready-%s', str_repeat('a', Nonce::SHORTEST)));
});

it('opens on the first stack paired, rather than choosing between them', function (): void {
    // Not a choice made on the operator's behalf: two stacks are not
    // interchangeable, and an operator with several meets the list. This is
    // only the answer to which one a launch that goes straight to one is about.
    $opening = new Opening(
        StacksInMemory::holding(aPairedMachine('a'), aPairedMachine('b')),
        ADeviceOnANetwork::connected(),
    );

    expect(howItOpened($opening))->toBe(sprintf('ready-%s', str_repeat('a', Nonce::SHORTEST)));
});

it('a paired device with no network opens blocked, naming the network', function (): void {
    // The whole of why the port exists. Before it, a phone in flight mode and a
    // machine that is switched off produced the same silence at the socket and
    // the app reported both as the machine — sending somebody to a cupboard to
    // check a stack that was fine.
    $opening = new Opening(
        StacksInMemory::holding(aPairedMachine()),
        ADeviceOnANetwork::withNothingToReachOver(),
    );

    expect(howItOpened($opening))->toBe(sprintf('blocked-%s', KindOfObstacle::DeviceHasNoNetwork->value));
});

it('a first run is never asked about the network', function (): void {
    // A device with no stack has nothing to reach, so the question has no
    // answer worth having. Told somebody their wifi was off, it would be
    // answering a question they have not asked yet — they have not paired a
    // machine, and that is the screen they need.
    $network = ADeviceOnANetwork::withNothingToReachOver();

    new Opening(StacksInMemory::working(), $network)->found();

    expect($network->timesAsked())->toBe(0);
});

it('a connected device is asked once and then left alone', function (): void {
    // A launch is not a poller. Asking twice is how a cheap question
    // becomes a habit, and the answer can change between the two — which would
    // make a launch that reported *ready* about a device that had since left
    // the network.
    $network = ADeviceOnANetwork::connected();

    new Opening(StacksInMemory::holding(aPairedMachine()), $network)->found();

    expect($network->timesAsked())->toBe(1);
});
