<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Remembered;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;
use Modules\Vault\Api\PlatformStacks;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\StacksInMemory;

// The Stacks contract, run against the adapter and against the fake.
//
// G2's shape. Every test that needs a paired device will hand its subject a
// `StacksInMemory` and never see a keychain — so if the fake is easier to
// satisfy than the platform, *two stacks are never mistaken for one* is being
// enforced against a store that always says yes.
//
// What is asserted is only what both must promise. The platform's store
// survives a launch and the fake does not, so "it is still there tomorrow"
// belongs to the adapter's own tests: a contract asserting it would either fail
// on the fake or be weakened to pass, and a weakened contract is how a fake
// drifts.
//
// The adapter is driven against a hand-written stand-in for the platform's own
// store, for the reason the secure-storage contract gives: there is no Keychain
// behind a PHP process on a laptop, and without one the adapter is a file
// nothing executes.

/** Named for this file: the root suites share one namespace (G10). */
function aStackCalled(string $called, string $seed = 'a', string $at = 'https://192.168.1.42'): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of($called),
        Address::of($at),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

/** Which arm answered, as a word. Named for this file, because the root suites share one namespace and a second `whatBecameOfIt` is a fatal at load (G10). */
function howTheStackWentDown(Remembered $remembered): string
{
    return $remembered->either(
        remembered: static fn(): Code => Code::of('remembered'),
        refused: static fn(WhyAStackCannotBeRemembered $why): Code => Code::of($why->value),
    )->shown();
}

/** @return array<string, array{Stacks}> */
dataset('every stacks implementation', [
    'the platform store' => [fn(): Stacks => new PlatformStacks(APlatformStore::working())],
    'the fake' => [fn(): Stacks => StacksInMemory::working()],
]);

it('N1-R35 — answers with nothing before anything has been paired', function (Stacks $stacks): void {
    expect($stacks->configured()->isEmpty())->toBeTrue();
})->with('every stacks implementation');

it('gives back the stack it was asked to remember', function (Stacks $stacks): void {
    expect(howTheStackWentDown($stacks->remember(aStackCalled('The loft'))))->toBe('remembered');

    $record = $stacks->configured();

    expect($record->isEmpty())->toBeFalse()
        ->and($record->stack(aStackCalled('The loft')->id())->name()->shown())->toBe('The loft');
})->with('every stacks implementation');

it('N1-R11 — keeps two paired machines apart', function (Stacks $stacks): void {
    $stacks->remember(aStackCalled('The loft', 'a'));
    $stacks->remember(aStackCalled('My mum\'s', 'b', 'https://192.168.1.77'));

    expect($stacks->configured())->toHaveCount(2);
})->with('every stacks implementation');

it('N1-R22 — re-pairing replaces a machine rather than adding a second row', function (Stacks $stacks): void {
    $stacks->remember(aStackCalled('The loft', 'a'));
    $stacks->remember(aStackCalled('The attic', 'a', 'https://192.168.1.99'));

    expect($stacks->configured())->toHaveCount(1)
        ->and($stacks->configured()->stack(aStackCalled('The attic', 'a')->id())->name()->shown())
        ->toBe('The attic');
})->with('every stacks implementation');

it('says a device with no store cannot remember a stack', function (): void {
    $refused = new PlatformStacks(APlatformStore::absent())->remember(aStackCalled('The loft'));

    expect(howTheStackWentDown($refused))->toBe(WhyAStackCannotBeRemembered::DeviceHasNoSecureStorage->value);
});

it('says a store that would not open cannot remember a stack', function (): void {
    $refused = new PlatformStacks(APlatformStore::refusing())->remember(aStackCalled('The loft'));

    expect(howTheStackWentDown($refused))->toBe(WhyAStackCannotBeRemembered::StoreWouldNotOpen->value);
});

it('the fake refuses for whichever reason it was given', function (): void {
    $refusing = StacksInMemory::refusing(WhyAStackCannotBeRemembered::DeviceHasNoSecureStorage);

    expect(howTheStackWentDown($refusing->remember(aStackCalled('The loft'))))
        ->toBe(WhyAStackCannotBeRemembered::DeviceHasNoSecureStorage->value)
        ->and($refusing->configured()->isEmpty())->toBeTrue();
});

// Whether the device holds a pairing, asked of the store.
//
// In the contract rather than in the adapter's own tests, because the whole
// point of the method is that two implementations agree: `Opening` asks it
// before a lock, every screen test asks the fake, and a fake that answered more
// readily than the platform would put the prompt in front of a first run on a
// device and never in a test.

it('N4-R22 — says a device holding nothing holds nothing', function (Stacks $stacks): void {
    expect($stacks->holdsAny())->toBeFalse();
})->with('every stacks implementation');

it('N4-R23 — says a device holds one the moment the store does', function (Stacks $stacks): void {
    $stacks->remember(aStackCalled('The loft'));

    expect($stacks->holdsAny())->toBeTrue();
})->with('every stacks implementation');

it('N4-R23 — never disagrees with the record it is asked about', function (Stacks $stacks): void {
    // The property, rather than the two answers above restated. The answer
    // comes from the store itself rather than a flag, and the way a flag goes
    // wrong is
    // not by being absent — it is by being right until something forgets to
    // maintain it, which is a disagreement between these two and nothing else.
    expect($stacks->holdsAny())->toBe(! $stacks->configured()->isEmpty());

    $stacks->remember(aStackCalled('The loft'));

    expect($stacks->holdsAny())->toBe(! $stacks->configured()->isEmpty());
})->with('every stacks implementation');

it('N4-R22 — a store that will not open is not a device holding nothing', function (): void {
    // The adapter's own, because the fake has no store to be unable to open.
    //
    // `Opening` asks this to decide whether there is anything worth locking, so
    // an unreadable store answered as *nothing* is the lock letting itself off
    // on exactly the device where something is already wrong: the pairings are
    // still there, the app simply cannot see them this launch. Answered as
    // *there may be*, the cost of being wrong is a prompt; the other way it is
    // an unlocked application.
    expect(new PlatformStacks(APlatformStore::absent())->holdsAny())->toBeTrue();
});

it('N4-R22 — reads a record written down as empty as holding nothing', function (): void {
    // The adapter's own, because it is about an encoding the fake does not
    // have: a store can hold the record and the record can be the empty list —
    // a device that paired a machine and then forgot it. A status check alone
    // reads that as *holds something*, which is a lock in front of nothing.
    $store = APlatformStore::working();
    $store->alreadyHolding('stacks', '[]');

    expect(new PlatformStacks($store)->holdsAny())->toBeFalse();
});
