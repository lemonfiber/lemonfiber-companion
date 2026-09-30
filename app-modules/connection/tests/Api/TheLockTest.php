<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function expect;
use function it;

use Modules\Connection\Api\TheLock;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Lock;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Vault\Api\PlatformStacks;

use function str_repeat;

use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\StacksInMemory;

/** A machine this device holds. Named for this file. */
function aPairingBehindTheLock(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The attic'),
        Address::of('https://192.168.1.44'),
        Fingerprint::of(str_repeat('e', Fingerprint::CHARACTERS)),
    );
}

/** Which arm the lock takes, as a word. Named for this file. */
function howTheLockStands(Lock $lock): string
{
    return $lock->either(
        held: static fn(): Code => Code::of('held'),
        open: static fn(): Code => Code::of('open'),
    )->shown();
}

it('stands where the store holds a pairing', function (): void {
    $lock = new TheLock(ADeviceThatKnowsYou::refusing(), StacksInMemory::holding(aPairingBehindTheLock()));

    expect(howTheLockStands($lock->standing()))->toBe('held');
});

it('is waived where the store holds nothing, so a first run is never asked', function (): void {
    $device = ADeviceThatKnowsYou::refusing();

    expect(howTheLockStands(new TheLock($device, StacksInMemory::working())->standing()))->toBe('open')
        ->and($device->asked())->toBe(0)
        ->and(howTheLockStands($device->standing()))->toBe('open');
});

it('is not waived where the store will not open, because it may hold a pairing', function (): void {
    $lock = new TheLock(ADeviceThatKnowsYou::refusing(), new PlatformStacks(APlatformStore::refusing()));

    expect(howTheLockStands($lock->standing()))->toBe('held');
});

it('stands again after a pairing is made, once the device stands it', function (): void {
    // The unlocked first run is not reachable once a pairing has been held: the
    // next time the device stands the lock, the store answers that something
    // is behind it.
    $device = ADeviceThatKnowsYou::refusing();
    $stacks = StacksInMemory::working();
    $lock = new TheLock($device, $stacks);

    $lock->standing();
    $stacks->remember(aPairingBehindTheLock());
    $device->standsAgain();

    expect(howTheLockStands($lock->standing()))->toBe('held');
});

it('reads nothing kept while the lock stands', function (): void {
    $stacks = StacksInMemory::holding(aPairingBehindTheLock());

    new TheLock(ADeviceThatKnowsYou::refusing(), $stacks)->standing();

    expect($stacks->timesAsked())->toBe(0);
});

it('asks the store nothing once the lock is open', function (): void {
    $stacks = StacksInMemory::working();
    $lock = new TheLock(ADeviceThatKnowsYou::unlocked(), $stacks);

    expect(howTheLockStands($lock->standing()))->toBe('open')
        ->and($stacks->timesAsked())->toBe(0);
});

it('never stands on a device with no screen lock', function (): void {
    $lock = new TheLock(ADeviceThatKnowsYou::withNoScreenLock(), StacksInMemory::holding(aPairingBehindTheLock()));

    expect(howTheLockStands($lock->standing()))->toBe('open');
});
