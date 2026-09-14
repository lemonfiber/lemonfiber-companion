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

    expect(howItOpened(new Opening(ADeviceThatKnowsYou::refusing(), $stacks)))->toBe('locked');
});

it('N4-R19 — nothing is asked of the stacks while the app is shut', function (): void {
    // The order is the requirement rather than a convenience, and this is the
    // assertion that pins it: a launch that read the stack list first would
    // pass the test above and still be wrong.
    $stacks = StacksInMemory::holding(aPairedMachine());

    new Opening(ADeviceThatKnowsYou::refusing(), $stacks)->found();

    expect($stacks->timesAsked())->toBe(0);
});

it('N1-R35 — a device with no stack opens unpaired, which is not a fault', function (): void {
    // A first run rather than a failure to reach. Reporting it as an obstacle
    // would be the app describing its own first launch as broken.
    expect(howItOpened(new Opening(ADeviceThatKnowsYou::willing(), StacksInMemory::working())))
        ->toBe('unpaired');
});

it('N1-R36 — a device with a stack opens ready, naming which', function (): void {
    $opening = new Opening(ADeviceThatKnowsYou::willing(), StacksInMemory::holding(aPairedMachine()));

    expect(howItOpened($opening))->toBe(sprintf('ready-%s', str_repeat('a', Nonce::SHORTEST)));
});

it('N4-R3 — a device offering no authentication is not a locked one', function (): void {
    // A handset with no passcode set. Refusing to open would be this app
    // requiring something the platform does not have, on a device where the
    // operator has already decided — which is the shape `N4-R3` refuses one
    // permission at a time.
    $opening = new Opening(ADeviceThatKnowsYou::withNoScreenLock(), StacksInMemory::holding(aPairedMachine()));

    expect(howItOpened($opening))->toBe(sprintf('ready-%s', str_repeat('a', Nonce::SHORTEST)));
});

it('N4-R3 — a device offering no authentication is never asked to unlock', function (): void {
    $device = ADeviceThatKnowsYou::withNoScreenLock();

    new Opening($device, StacksInMemory::holding(aPairedMachine()))->found();

    expect($device->asked())->toBe(0);
});

it('N1-R31 — opens on the first stack paired, rather than choosing between them', function (): void {
    // Not a choice made on the operator's behalf: two stacks are not
    // interchangeable, and an operator with several meets the list. This is
    // only the answer to which one a launch that goes straight to one is about.
    $opening = new Opening(
        ADeviceThatKnowsYou::willing(),
        StacksInMemory::holding(aPairedMachine('a'), aPairedMachine('b')),
    );

    expect(howItOpened($opening))->toBe(sprintf('ready-%s', str_repeat('a', Nonce::SHORTEST)));
});
