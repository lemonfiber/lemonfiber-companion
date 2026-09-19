<?php

declare(strict_types=1);

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Kept;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Resumed;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhySessionCannotBeKept;
use Modules\Vault\Api\PlatformKeychain;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\APlatformStore;

// The SecureStorage contract, run against the adapter and against the fake.
//
// G2's shape, and the reason it matters most here: every other test in this
// repository that needs somewhere to put a session will hand its subject an
// `AKeychainInMemory` and never see a keychain at all. If the fake is easier to
// satisfy than the platform, *nothing secret is written where it can be read*
// is enforced against a store that always says yes.
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
// The adapter is driven against a hand-written stand-in for the platform's own
// store. There is no Keychain behind a PHP process on a laptop, and without the
// stand-in the adapter is a file nothing executes — so the three things it
// actually decides (which key a stack gets, which refusal a failure is, that
// forgetting ignores the result) would go unchecked until somebody held a phone.
//
// A subclass written out by hand rather than a mock, which is what G1 asks for:
// a mock asserts on calls and drifts silently when the real class changes, and
// this fails to compile.

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

    expect(howItWent($keychain->keep(aStackThatIsPaired(), Session::of(THE_TOKEN), Whose::theOperator())))->toBe('kept');
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

    expect(howItWent($keychain->keep(aStackThatIsPaired(), Session::of(THE_TOKEN), Whose::theOperator())))
        ->toBe('no_secure_storage');
});

it('N4-R6 — a store that will not open is not a device that has none', function (): void {
    // Different remedies: one is worth trying again and the other sends an
    // operator to a settings screen. Folding them together tells somebody their
    // device cannot do a thing it can.
    $keychain = AKeychainInMemory::thatWillNotOpen();

    expect(howItWent($keychain->keep(aStackThatIsPaired(), Session::of(THE_TOKEN), Whose::theOperator())))
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

    $keychain->keep(aStackThatIsPaired(), Session::of(THE_TOKEN), Whose::theOperator());
    $keychain->keep($other, Session::of('another-session-not-a-secret'), Whose::theOperator());
    $keychain->forget($other);

    expect($keychain->isHolding(aStackThatIsPaired()))->toBeTrue();
});

/** Which session came back, or the word for none — whichever arm answered. */
function whatWasResumed(Resumed $resumed): string
{
    return $resumed->either(
        held: static fn(Session $session): Code => Code::of($session->forTheHeader()),
        notHeld: static fn(): Code => Code::of('nothing'),
    )->shown();
}

/**
 * Both stores, each holding a session for the paired stack.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed back
 * as one argument, so a pair returns as an array where the test wanted two
 * parameters.
 *
 * @return array<string, SecureStorage>
 */
function everyStoreHolding(): array
{
    $fake = AKeychainInMemory::working();
    $adapter = new PlatformKeychain(APlatformStore::working());

    foreach ([$fake, $adapter] as $store) {
        $store->keep(aStackThatIsPaired(), Session::of(THE_TOKEN), Whose::theOperator());
    }

    return ['the fake' => $fake, 'the adapter' => $adapter];
}

/**
 * Whose a resumed session is, as a word.
 *
 * Beside {@see whatWasResumed()} rather than folded into it: a test that read one
 * string carrying both would pass on a store that returned them crossed.
 */
function whoseItIs(Resumed $resumed): string
{
    return $resumed->either(
        held: static fn(Session $session, Whose $whose): Code => $whose->either(
            operator: static fn(): Code => Code::of('the operator'),
            member: static fn(string $id): Code => Code::of($id),
        ),
        notHeld: static fn(): Code => Code::of('nothing'),
    )->shown();
}

it('N1-R7 — gives back the session it was keeping, so nothing asks twice', function (): void {
    // The whole point of keeping one. The password is exchanged once, and
    // "once" is only true if the next launch finds what the first one kept.
    foreach (everyStoreHolding() as $which => $store) {
        expect(whatWasResumed($store->resume(aStackThatIsPaired())))->toBe(THE_TOKEN, $which);
    }
});

it('has nothing for a stack it was never given one for', function (): void {
    $never = StackId::of(Nonce::of('b2c3d4e5f6071829'));

    foreach (everyStoreHolding() as $which => $store) {
        expect(whatWasResumed($store->resume($never)))->toBe('nothing', $which);
    }
});

it('N1-R11 — resumes each stack its own session, never the other one', function (): void {
    // The failure this would hide is the worst one in the file: a reader keyed
    // loosely hands stack B the session stack A opened, and every request after
    // that is attributed to the wrong machine.
    $other = StackId::of(Nonce::of('b2c3d4e5f6071829'));

    foreach (everyStoreHolding() as $which => $store) {
        $store->keep($other, Session::of('another-session-not-a-secret'), Whose::theOperator());

        expect(whatWasResumed($store->resume(aStackThatIsPaired())))->toBe(THE_TOKEN, $which)
            ->and(whatWasResumed($store->resume($other)))->toBe('another-session-not-a-secret', $which);
    }
});

it('N3-R1 — gives back whose the session is, not only what it is', function (): void {
    // The half a resumed session is useless without. A store that hands the token
    // back and forgets who it was minted for sends every launch to the operator's
    // application, whoever signed in — which is the requirement read backwards.
    $member = Whose::member('a7f3');

    foreach (everyStoreHolding() as $which => $store) {
        $store->keep(aStackThatIsPaired(), Session::of(THE_TOKEN), $member);

        expect(whoseItIs($store->resume(aStackThatIsPaired())))->toBe('a7f3', $which);
    }
});

it('N3-R1 — keeps each stack its own subject, never the other one', function (): void {
    // The same failure the rule about resuming each stack its own session names, one
    // field along and worse: a subject read across stacks shows one household member
    // another household's application.
    $other = StackId::of(Nonce::of('b2c3d4e5f6071829'));

    foreach (everyStoreHolding() as $which => $store) {
        $store->keep(aStackThatIsPaired(), Session::of(THE_TOKEN), Whose::member('a7f3'));
        $store->keep($other, Session::of('another-session-not-a-secret'), Whose::theOperator());

        expect(whoseItIs($store->resume(aStackThatIsPaired())))->toBe('a7f3', $which)
            ->and(whoseItIs($store->resume($other)))->toBe('the operator', $which);
    }
});

it('N3-R1 — a session kept for the operator comes back as the operator', function (): void {
    foreach (everyStoreHolding() as $which => $store) {
        expect(whoseItIs($store->resume(aStackThatIsPaired())))->toBe('the operator', $which);
    }
});

it('has nothing once the session has been forgotten', function (): void {
    foreach (everyStoreHolding() as $which => $store) {
        $store->forget(aStackThatIsPaired());

        expect(whatWasResumed($store->resume(aStackThatIsPaired())))->toBe('nothing', $which);
    }
});

it('N4-R6 — a store that will not open has no session rather than a fault', function (): void {
    // Both refusals answer the same way here, which is the one place this port
    // does *not* tell them apart. A keychain that cannot be read is a keychain
    // with no session in it as far as resuming goes: the operator is asked for
    // the password, which is the honest outcome and the only useful one. A
    // screen saying something went wrong would have nothing to offer them.
    $stores = [
        'the fake with no store' => AKeychainInMemory::withNowhereSafe(),
        'the fake that will not open' => AKeychainInMemory::thatWillNotOpen(),
        'the adapter with no store' => new PlatformKeychain(APlatformStore::absent()),
        'the adapter that will not open' => new PlatformKeychain(APlatformStore::refusing()),
    ];

    foreach ($stores as $which => $store) {
        expect(whatWasResumed($store->resume(aStackThatIsPaired())))->toBe('nothing', $which);
    }
});

it('reads a store that answered Found with nothing as holding nothing', function (): void {
    // The adapter's own case: `Session::of()` refuses a blank, and a store that
    // says `Found` and hands back an empty string has lost the value rather
    // than kept a session. Letting that raise would put a fatal on the launch
    // screen; the operator is offered the password instead.
    //
    // Only the adapter can be asked — the fake holds `Session` objects, which
    // cannot be blank, so this is the branch a contract run against both would
    // never reach.
    $store = APlatformStore::working();
    $store->alreadyHolding(sprintf('lemonfiber.session.%s', A_PAIRED_STACK), '');

    expect(whatWasResumed(new PlatformKeychain($store)->resume(aStackThatIsPaired())))->toBe('nothing');
});

it('PlatformKeychain answers the same port', function (): void {
    expect(PlatformKeychain::class)->toImplement(SecureStorage::class);
});

it('N4-R5 — the adapter keeps a session where the platform keeps things', function (): void {
    $store = APlatformStore::working();
    $keychain = new PlatformKeychain($store);

    expect($keychain->isAvailable())->toBeTrue();
    expect(howItWent($keychain->keep(aStackThatIsPaired(), Session::of(THE_TOKEN), Whose::theOperator())))->toBe('kept');
    expect($store->keysHeld())->toBe([sprintf('lemonfiber.session.%s', A_PAIRED_STACK)]);
});

it('N1-R11 — the adapter gives each stack its own key', function (): void {
    // One key holding "the session" is how the second pairing overwrites the
    // first, and the first stack starts answering with somebody else's
    // credential. The fake cannot show this — it is keyed the same way by
    // construction — so it is checked here, against the thing that builds keys.
    $store = APlatformStore::working();
    $keychain = new PlatformKeychain($store);

    $keychain->keep(aStackThatIsPaired(), Session::of(THE_TOKEN), Whose::theOperator());
    $keychain->keep(StackId::of(Nonce::of('b2c3d4e5f6071829')), Session::of('another-session-not-a-secret'), Whose::theOperator());

    expect($store->keysHeld())->toHaveCount(2);
});

it('N4-R6 — the adapter tells a device with no store from one that refused', function (): void {
    // The distinction the whole refusal type exists for, and the one place it
    // is read off the platform rather than decided by us.
    expect(howItWent(new PlatformKeychain(APlatformStore::absent())
        ->keep(aStackThatIsPaired(), Session::of(THE_TOKEN), Whose::theOperator())))->toBe('no_secure_storage');

    expect(howItWent(new PlatformKeychain(APlatformStore::refusing())
        ->keep(aStackThatIsPaired(), Session::of(THE_TOKEN), Whose::theOperator())))->toBe('store_would_not_open');

    expect(new PlatformKeychain(APlatformStore::absent())->isAvailable())->toBeFalse();
});

it('N4-R6 — the adapter forgets even where the store said no', function (): void {
    // The one thing that must always work: a refusal leaves the app holding a
    // session it could not store, and getting rid of it cannot depend on the
    // store that just refused. The adapter ignores the delete result for
    // exactly this reason.
    expect(howItWent(new PlatformKeychain(APlatformStore::absent())->forget(aStackThatIsPaired())))
        ->toBe('kept');
});

it('N3-R1 — reads a session kept before members as the operator\'s', function (): void {
    // What a device that signed in under an earlier build is holding: the token and
    // nothing else, written when there was no member who could sign in. Read as the
    // operator's because that is what it is, not because a missing subject is treated
    // leniently — and read at all, which is the half that matters: the alternative
    // asks somebody for a password they have already given.
    $store = APlatformStore::working()
        ->alreadyHolding(sprintf('lemonfiber.session.%s', A_PAIRED_STACK), THE_TOKEN);

    $resumed = new PlatformKeychain($store)->resume(aStackThatIsPaired());

    expect(whatWasResumed($resumed))->toBe(THE_TOKEN)
        ->and(whoseItIs($resumed))->toBe('the operator');
});

it('N4-R5 — a stored value with a subject and no token is no session', function (): void {
    // The half-written value, which is the one shape this encoding can be left in. A
    // subject with no token is a member this app cannot ask anything for, and saying
    // so sends them to the password field rather than to a request that cannot be made.
    $store = APlatformStore::working()
        ->alreadyHolding(sprintf('lemonfiber.session.%s', A_PAIRED_STACK), "a7f3\n");

    expect(whatWasResumed(new PlatformKeychain($store)->resume(aStackThatIsPaired())))
        ->toBe('nothing');
});
