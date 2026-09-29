<?php

declare(strict_types=1);

use Modules\Device\Api\SystemEntropy;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Sealing;
use Modules\Kernel\Api\SealStanding;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\Unsealing;
use Modules\Kernel\Api\WhyNothingIsSealed;
use Modules\Seal\Api\EncrypterSeal;
use Modules\Vault\Api\PlatformSealKeys;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\ASealInMemory;

// The Sealed contract, run against the adapter and against the fake.
//
// Every owner that keeps something will seal it through an `ASealInMemory` in
// its own tests and never meet a cipher, so what the fake promises is what
// those tests are written against. A fake easier to satisfy than the adapter —
// one whose payloads open anywhere, or whose stack hash is the stack's
// identity — would make every one of them green about a phone that writes
// what a stack said to disk in the clear.
//
// The adapter runs over the vault's real key store, which runs over a
// hand-written stand-in for the platform's store, with keys drawn from the
// platform's own randomness, so that two phones here hold two keys as two
// handsets would. Everything between a value and its payload is the shipped
// code; only the device is stood in for.

/** The stack these assertions seal things for. */
const A_STACK_TO_SEAL_FOR = 'a1b2c3d4e5f60718';

/** A second stack, which must never share the first one's hash. */
const ANOTHER_STACK_TO_SEAL_FOR = 'b2c3d4e5f6071829';

/** What is sealed: the kind of thing a stack says, and the reason it is sealed. */
const WHAT_IS_SEALED = '{"summary":"the disk that holds the household\'s photos is nearly full"}';

function aStackToSealFor(): StackId
{
    return StackId::of(Nonce::of(A_STACK_TO_SEAL_FOR));
}

function anotherStackToSealFor(): StackId
{
    return StackId::of(Nonce::of(ANOTHER_STACK_TO_SEAL_FOR));
}

/** The adapter, over a platform store in whatever state the test asks for. */
function theSealOver(APlatformStore $store): EncrypterSeal
{
    return new EncrypterSeal(new PlatformSealKeys($store), new SystemEntropy());
}

/**
 * Each seal on a phone whose secure storage works and holds no key yet.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed
 * back as one argument.
 *
 * @return array<string, Sealed>
 */
function everySealOnAFirstLaunch(): array
{
    return [
        'the adapter' => theSealOver(APlatformStore::working()),
        'the fake' => ASealInMemory::working(),
    ];
}

/**
 * Each seal on a phone whose secure storage cannot be had, for one reason.
 *
 * Matched rather than branched on, so a third reason is a build error here
 * rather than a phone this quietly stops describing.
 *
 * @return array<string, Sealed>
 */
function everySealThatCannotBeHad(WhyNothingIsSealed $why): array
{
    return match ($why) {
        WhyNothingIsSealed::NoSecureStorage => [
            'the adapter' => theSealOver(APlatformStore::absent()),
            'the fake' => ASealInMemory::withNoSecureStorage(),
        ],
        WhyNothingIsSealed::KeyUnreadable => [
            'the adapter' => theSealOver(APlatformStore::refusing()),
            'the fake' => ASealInMemory::thatWillNotOpen(),
        ],
    };
}

/**
 * Each seal, having sealed a value, after its keys have gone.
 *
 * What a restore onto another device or a reset keychain leaves: the payload
 * is still on disk and the keys it was sealed under are not.
 *
 * @return array<string, array{Sealed, SealedPayload}>
 */
function everySealThatLostItsKeys(): array
{
    $store = APlatformStore::working();
    $adapter = theSealOver($store);
    $adapter->standing();
    $sealedByTheAdapter = thePayloadOf($adapter->seal(Unsealed::of(WHAT_IS_SEALED)));
    $store->forget('lemonfiber.seal.data');
    $store->forget('lemonfiber.seal.stack');

    $fake = ASealInMemory::working();
    $fake->standing();
    $sealedByTheFake = thePayloadOf($fake->seal(Unsealed::of(WHAT_IS_SEALED)));
    $fake->losesItsKeys();

    return [
        'the adapter' => [$adapter, $sealedByTheAdapter],
        'the fake' => [$fake, $sealedByTheFake],
    ];
}

/**
 * The payload a sealing answered with.
 *
 * A refusal answers a payload that is not one, so a test expecting a seal and
 * given a refusal fails at the assertion that the payload opens.
 */
function thePayloadOf(Sealing $sealing): SealedPayload
{
    return $sealing->either(
        sealed: static fn(SealedPayload $payload): SealedPayload => $payload,
        refused: static fn(): SealedPayload => SealedPayload::of(''),
    );
}

/** Which arm a sealing answered on, as a word. */
function howTheSealWent(Sealing $sealing): string
{
    return $sealing->either(
        sealed: static fn(): Code => Code::of('sealed'),
        refused: static fn(WhyNothingIsSealed $why): Code => Code::of(sprintf('refused:%s', $why->name)),
    )->shown();
}

/** What a payload opened to, bracketed so an empty value reads as one, or the word for none. */
function whatItOpenedTo(Unsealing $opening): string
{
    return $opening->either(
        opened: static fn(Unsealed $value): Code => Code::of(sprintf('[%s]', $value->inTheClear())),
        unreadable: static fn(): Code => Code::of('unreadable'),
    )->shown();
}

/** One character of a payload changed: the middle one, to a character it is not. */
function alteredByOneCharacter(SealedPayload $payload): SealedPayload
{
    $said = $payload->forTheStore();
    $middle = intdiv(mb_strlen($said), 2);
    $swapped = mb_substr($said, $middle, 1) === 'A' ? 'B' : 'A';

    return SealedPayload::of(sprintf('%s%s%s', mb_substr($said, 0, $middle), $swapped, mb_substr($said, $middle + 1)));
}

it('opens a sealed value back to the same value', function (): void {
    foreach (everySealOnAFirstLaunch() as $which => $seal) {
        $payload = thePayloadOf($seal->seal(Unsealed::of(WHAT_IS_SEALED)));

        expect(whatItOpenedTo($seal->open($payload)))->toBe(sprintf('[%s]', WHAT_IS_SEALED), $which);
    }
});

it('opens a sealed empty value as the empty value', function (): void {
    foreach (everySealOnAFirstLaunch() as $which => $seal) {
        expect(whatItOpenedTo($seal->open(thePayloadOf($seal->seal(Unsealed::of(''))))))->toBe('[]', $which);
    }
});

it('seals one value differently every time, and each opens', function (): void {
    // A payload that repeats says which kept values are the same, which is
    // something to learn about a household without the key.
    foreach (everySealOnAFirstLaunch() as $which => $seal) {
        $first = thePayloadOf($seal->seal(Unsealed::of(WHAT_IS_SEALED)));
        $second = thePayloadOf($seal->seal(Unsealed::of(WHAT_IS_SEALED)));

        expect($first->forTheStore())->not->toBe($second->forTheStore(), $which)
            ->and(whatItOpenedTo($seal->open($first)))->toBe(sprintf('[%s]', WHAT_IS_SEALED), $which)
            ->and(whatItOpenedTo($seal->open($second)))->toBe(sprintf('[%s]', WHAT_IS_SEALED), $which);
    }
});

it('writes nothing of the value into the payload', function (): void {
    foreach (everySealOnAFirstLaunch() as $which => $seal) {
        expect(thePayloadOf($seal->seal(Unsealed::of(WHAT_IS_SEALED)))->forTheStore())
            ->not->toContain('photos', $which);
    }
});

it('does not open a payload altered by one character', function (): void {
    foreach (everySealOnAFirstLaunch() as $which => $seal) {
        $payload = thePayloadOf($seal->seal(Unsealed::of(WHAT_IS_SEALED)));

        expect(whatItOpenedTo($seal->open(alteredByOneCharacter($payload))))->toBe('unreadable', $which);
    }
});

it('does not open a payload another phone sealed', function (): void {
    // Two phones, each with keys of its own: what one sealed is foreign to
    // the other, which is what a backup restored onto a second phone holds.
    $others = everySealOnAFirstLaunch();

    foreach (everySealOnAFirstLaunch() as $which => $seal) {
        $seal->standing();
        $others[$which]->standing();
        $foreign = thePayloadOf($others[$which]->seal(Unsealed::of(WHAT_IS_SEALED)));

        expect(whatItOpenedTo($seal->open($foreign)))->toBe('unreadable', $which);
    }
});

it('does not open a string that was never a payload', function (): void {
    foreach (everySealOnAFirstLaunch() as $which => $seal) {
        $seal->standing();

        expect(whatItOpenedTo($seal->open(SealedPayload::of('not-a-payload'))))->toBe('unreadable', $which)
            ->and(whatItOpenedTo($seal->open(SealedPayload::of(''))))->toBe('unreadable', $which);
    }
});

it('reads a missing key as made afresh, and then as held', function (): void {
    foreach (everySealOnAFirstLaunch() as $which => $seal) {
        expect($seal->standing())->toBe(SealStanding::MadeAfresh, $which)
            ->and($seal->standing())->toBe(SealStanding::Held, $which);
    }
});

it('reads keys that have gone as made afresh, and does not open what they sealed', function (): void {
    foreach (everySealThatLostItsKeys() as $which => [$seal, $payload]) {
        expect($seal->standing())->toBe(SealStanding::MadeAfresh, $which)
            ->and(whatItOpenedTo($seal->open($payload)))->toBe('unreadable', $which)
            ->and($seal->standing())->toBe(SealStanding::Held, $which);
    }
});

it('reads a phone with no secure storage as unavailable, and refuses to seal saying so', function (): void {
    foreach (everySealThatCannotBeHad(WhyNothingIsSealed::NoSecureStorage) as $which => $seal) {
        expect($seal->standing())->toBe(SealStanding::Unavailable, $which)
            ->and(howTheSealWent($seal->seal(Unsealed::of(WHAT_IS_SEALED))))->toBe('refused:NoSecureStorage', $which)
            ->and(whatItOpenedTo($seal->open(SealedPayload::of('not-a-payload'))))->toBe('unreadable', $which);
    }
});

it('reads a store that will not open as unavailable, and refuses to seal as a key it cannot read', function (): void {
    // Not made afresh: a new key here would throw away everything the old one
    // sealed, for a store that may open next time.
    foreach (everySealThatCannotBeHad(WhyNothingIsSealed::KeyUnreadable) as $which => $seal) {
        expect($seal->standing())->toBe(SealStanding::Unavailable, $which)
            ->and(howTheSealWent($seal->seal(Unsealed::of(WHAT_IS_SEALED))))->toBe('refused:KeyUnreadable', $which);
    }
});

it('names one stack by the same hash every time the key is held', function (): void {
    foreach (everySealOnAFirstLaunch() as $which => $seal) {
        expect($seal->stack(aStackToSealFor())->forTheStore())
            ->toBe($seal->stack(aStackToSealFor())->forTheStore(), $which);
    }
});

it('names two stacks by two hashes', function (): void {
    foreach (everySealOnAFirstLaunch() as $which => $seal) {
        expect($seal->stack(aStackToSealFor())->forTheStore())
            ->not->toBe($seal->stack(anotherStackToSealFor())->forTheStore(), $which);
    }
});

it('never puts the stack\'s identity in its hash', function (): void {
    $seals = [
        ...everySealOnAFirstLaunch(),
        ...array_combine(
            ['the adapter with no store', 'the fake with no store'],
            everySealThatCannotBeHad(WhyNothingIsSealed::NoSecureStorage),
        ),
    ];

    foreach ($seals as $which => $seal) {
        expect($seal->stack(aStackToSealFor())->forTheStore())->not->toContain(A_STACK_TO_SEAL_FOR, $which);
    }
});

it('names a stack by a hash that matches nothing where no key can be had', function (): void {
    // There is nowhere to keep a key, so there is no hash a row could be found
    // by again — and nothing is kept on such a phone to be found.
    foreach (WhyNothingIsSealed::cases() as $why) {
        foreach (everySealThatCannotBeHad($why) as $which => $seal) {
            expect($seal->stack(aStackToSealFor())->forTheStore())
                ->not->toBe($seal->stack(aStackToSealFor())->forTheStore(), sprintf('%s, %s', $which, $why->name));
        }
    }
});
