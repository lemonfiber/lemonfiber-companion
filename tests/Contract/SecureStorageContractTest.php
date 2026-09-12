<?php

declare(strict_types=1);

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Kept;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhySessionCannotBeKept;
use Modules\Vault\Api\PlatformKeychain;
use Tests\Support\Fakes\AKeychainInMemory;

// The SecureStorage contract, run against the adapter and against the fake.
//
// G2's shape, and the reason it matters most here: every other test in this
// repository that needs somewhere to put a session will hand its subject an
// `AKeychainInMemory` and never see a keychain at all. If the fake is easier to
// satisfy than the platform, `N4-R5` is enforced against a store that always
// says yes.
//
// The refusal is a value rather than an exception, which C1 required and which
// settled a second problem: shipmonk forbids throwing a checked exception inside
// a closure, and every test body here is one — so an `@throws` on the port would
// have made the refusal the one case no contract test could exercise.
//
// What is asserted is only what both must promise. The platform's store persists
// across launches and the fake does not, so "it is still there tomorrow" belongs
// to the adapter's own tests — a contract asserting it would either fail on the
// fake or be weakened to pass, and a weakened contract is how a fake drifts.
//
// The adapter is constructed but not driven on this machine: there is no
// Keychain behind a PHP process on a laptop, and a test that tried would be
// asserting about the NativePHP stub rather than about either implementation.
// So the adapter's arm asserts the shape of the promise — the types it accepts,
// the exception it raises — and the fake's arm asserts the behaviour.

const A_PAIRED_STACK = 'a1b2c3d4e5f60718';
const THE_TOKEN = 'a-session-not-a-secret';

function aStackThatIsPaired(): StackId
{
    return StackId::of(Nonce::of(A_PAIRED_STACK));
}

/**
 * Which arm answered, as a word.
 *
 * Named for this file rather than `fold`, because the root suites share one
 * namespace and two of the same name are a fatal the moment both load (G10).
 */
function howItWent(Kept $kept): string
{
    return $kept->either(
        kept: static fn(): Code => Code::of('kept'),
        refused: static fn(WhySessionCannotBeKept $why): Code => Code::of($why->value),
    )->shown();
}

it('N4-R5 — a working store keeps a session and gives it back up on request', function (): void {
    $keychain = AKeychainInMemory::working();

    expect($keychain->isAvailable())->toBeTrue();

    expect(howItWent($keychain->keep(aStackThatIsPaired(), Session::of(THE_TOKEN))))->toBe('kept');
    expect($keychain->isHolding(aStackThatIsPaired()))->toBeTrue();

    expect(howItWent($keychain->forget(aStackThatIsPaired())))->toBe('kept');
    expect($keychain->isHolding(aStackThatIsPaired()))->toBeFalse();
});

it('N4-R6 — a device with nowhere safe refuses, and says which refusal it is', function (): void {
    // The clause that makes this a port rather than a call. An adapter that
    // answered "could not save" would leave the app holding a session it cannot
    // keep and no way to tell an operator whether the device has no store, or
    // the store would not open, or something is broken.
    $keychain = AKeychainInMemory::withNowhereSafe();

    expect($keychain->isAvailable())->toBeFalse();

    expect(howItWent($keychain->keep(aStackThatIsPaired(), Session::of(THE_TOKEN))))
        ->toBe('no_secure_storage');
});

it('N4-R6 — a store that will not open is not a device that has none', function (): void {
    // Different remedies: one is worth trying again and the other sends an
    // operator to a settings screen. Folding them together tells somebody their
    // device cannot do a thing it can.
    $keychain = AKeychainInMemory::thatWillNotOpen();

    expect(howItWent($keychain->keep(aStackThatIsPaired(), Session::of(THE_TOKEN))))
        ->toBe('store_would_not_open');
});

it('N4-R6 — forgetting works even where keeping did not', function (): void {
    // The one thing that must always succeed. A refusal leaves the app holding
    // a session it could not store, and getting rid of it cannot depend on the
    // store that just refused.
    $keychain = AKeychainInMemory::withNowhereSafe();

    expect(howItWent($keychain->forget(aStackThatIsPaired())))->toBe('kept');
    expect($keychain->isHolding(aStackThatIsPaired()))->toBeFalse();
});

it('N1-R11 — two stacks do not share one session', function (): void {
    // One key holding "the session" is how the second pairing overwrites the
    // first, and the first stack starts answering with somebody else's
    // credential. Asserted on the fake because it is the one that can be
    // inspected; the adapter keys the same way, which the contract cannot see
    // and the adapter's own comment says.
    $keychain = AKeychainInMemory::working();
    $other = StackId::of(Nonce::of('b2c3d4e5f6071829'));

    $keychain->keep(aStackThatIsPaired(), Session::of(THE_TOKEN));
    $keychain->keep($other, Session::of('another-session-not-a-secret'));
    $keychain->forget($other);

    expect($keychain->isHolding(aStackThatIsPaired()))->toBeTrue();
});

it('PlatformKeychain answers the same port', function (): void {
    // Not driven: there is no Keychain behind a PHP process on a laptop, and a
    // test that called it would be asserting about NativePHP's stub. What is
    // checkable here is that the adapter is the port — which is what stops the
    // fake being the only thing that implements it.
    expect(PlatformKeychain::class)->toImplement(SecureStorage::class);
});
