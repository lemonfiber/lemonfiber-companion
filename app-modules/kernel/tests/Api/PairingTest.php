<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function json_encode;

use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\FingerprintIsNotAFingerprint;
use Modules\Kernel\Api\HowItWasRead;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\InstantIsBeforeTheEpoch;
use Modules\Kernel\Api\Pairing;
use Modules\Kernel\Api\PairingIsNotReadable;
use Modules\Kernel\Api\PairingIsSpent;
use Tests\Support\Fakes\FrozenClock;

const A_STACKS_DIGEST = '3b8c1f09a7d24e6b5c0f81a2d93e47b6c8150af2937d6e4b1c05a8f39d27e64b';
const A_STACKS_ADDRESS = 'https://stack.local';

/** The moment every test in this file happens at. */
const NOW = 1_757_808_000;

/** Far enough ahead that only a test about expiry has to think about it. */
const WHILE_IT_IS_GOOD = NOW + 300;

/**
 * Material as a stack would produce it, with any half removable.
 *
 * @param array<string, mixed> $also anything the format does not define
 */
function material(
    ?string $address = A_STACKS_ADDRESS,
    ?string $fingerprint = A_STACKS_DIGEST,
    ?int $expires = WHILE_IT_IS_GOOD,
    array $also = [],
): string {
    $said = [];

    if ($address !== null) {
        $said['address'] = $address;
    }

    if ($fingerprint !== null) {
        $said['fingerprint'] = $fingerprint;
    }

    if ($expires !== null) {
        $said['expires'] = $expires;
    }

    return (string) json_encode([...$said, ...$also]);
}

/** A clock stopped at the moment these tests happen. */
function whenItIsRead(int $at = NOW): FrozenClock
{
    return FrozenClock::at(Instant::atEpochSeconds($at));
}

it('N1-R18 — carries the fingerprint the stack will present', function (): void {
    // `ADR-0018`: the fingerprint comes from this material and never from the
    // network. A fingerprint learned from the connection it is meant to
    // validate proves nothing — somebody carried this across the gap, and the
    // gap is what makes it worth anything.
    $paired = Pairing::read(material(), HowItWasRead::Scanned, whenItIsRead());

    expect($paired->at()->forTheClient())->toBe(A_STACKS_ADDRESS)
        ->and($paired->presenting()->is(
            Fingerprint::of(A_STACKS_DIGEST),
        ))->toBeTrue();
});

it('N1-R6 — reads the same material whichever way it arrived', function (): void {
    // Both routes, one parser. A code read by camera is not more trusted than
    // one read by a person, and a parser that branched here would be two
    // parsers of which only one would stay tested.
    $scanned = Pairing::read(material(), HowItWasRead::Scanned, whenItIsRead());
    $typed = Pairing::read(material(), HowItWasRead::Typed, whenItIsRead());

    expect($scanned->at()->is($typed->at()))->toBeTrue()
        ->and($scanned->presenting()->is($typed->presenting()))->toBeTrue();
});

it('N1-R6 — remembers which way it arrived', function (): void {
    expect(Pairing::read(material(), HowItWasRead::Typed, whenItIsRead())->how())->toBe(HowItWasRead::Typed)
        ->and(Pairing::read(material(), HowItWasRead::Scanned, whenItIsRead())->how())->toBe(HowItWasRead::Scanned);
});

it('refuses something that is not material at all', function (): void {
    expect(fn(): Pairing => Pairing::read('not json', HowItWasRead::Scanned, whenItIsRead()))
        ->toThrow(PairingIsNotReadable::class, 'did not carry an address');
});

it('names which half was missing, and how it was read', function (): void {
    // The message is for whoever reads the log, and the two halves mean
    // different things: material with no fingerprint is a stack that produced
    // it wrongly, not an operator who read it wrongly.
    expect(fn(): Pairing => Pairing::read(material(fingerprint: null), HowItWasRead::Typed, whenItIsRead()))
        ->toThrow(PairingIsNotReadable::class, 'read by typed carried no fingerprint');

    expect(fn(): Pairing => Pairing::read(material(address: null), HowItWasRead::Scanned, whenItIsRead()))
        ->toThrow(PairingIsNotReadable::class, 'read by scanned carried no address');
});

it('refuses a half that is present and empty', function (): void {
    // The case a coalesce would have turned into "absent" and this refuses by
    // name: the key is there, and there is nothing in it.
    expect(fn(): Pairing => Pairing::read(material(address: '   '), HowItWasRead::Typed, whenItIsRead()))
        ->toThrow(PairingIsNotReadable::class, 'carried no address');
});

it('lets the address and the fingerprint refuse in their own words', function (): void {
    // Deliberately not wrapped. A malformed digest inside well-formed material
    // is `Fingerprint`'s refusal to explain, and wrapping it would replace a
    // message naming the problem with one naming the envelope.
    expect(fn(): Pairing => Pairing::read(material(fingerprint: 'nowhere near a digest'), HowItWasRead::Scanned, whenItIsRead()))
        ->toThrow(FingerprintIsNotAFingerprint::class);
});

it('N1-R6 — says which failure is worth simply retrying', function (): void {
    // "Try again" is advice somebody has already taken if they typed sixty-four
    // characters. A camera can be re-pointed; a transcription needs showing
    // what did not parse.
    expect(HowItWasRead::Scanned->isWorthSimplyRetrying())->toBeTrue()
        ->and(HowItWasRead::Typed->isWorthSimplyRetrying())->toBeFalse();
});

it('N1-R49 — material that has expired is refused', function (): void {
    // A photograph of a QR code in somebody's camera roll is a durable
    // instruction to trust a host. It outlives the evening it was useful for,
    // and whoever picks the phone up later can act on it.
    expect(fn(): Pairing => Pairing::read(material(expires: NOW - 1), HowItWasRead::Scanned, whenItIsRead()))
        ->toThrow(PairingIsSpent::class);
});

it('N1-R49 — material expiring at this very second is spent', function (): void {
    // A boundary that admits the exact second is a boundary two clocks
    // disagree about, and the stack's clock is not this phone's.
    expect(fn(): Pairing => Pairing::read(material(expires: NOW), HowItWasRead::Typed, whenItIsRead()))
        ->toThrow(PairingIsSpent::class);
});

it('N1-R49 — says the code is stale rather than that it is wrong', function (): void {
    // Different sentences to the person holding the phone. "That is not a
    // pairing code" sends somebody to check what they scanned; "that code has
    // expired" sends them back to the machine for another. Telling the first
    // to somebody who did everything right is how an operator learns that this
    // screen is unreliable.
    expect(fn(): Pairing => Pairing::read(material(expires: NOW - 1), HowItWasRead::Scanned, whenItIsRead()))
        ->toThrow(PairingIsSpent::class)
        ->and(fn(): Pairing => Pairing::read(material(expires: null), HowItWasRead::Scanned, whenItIsRead()))
        ->toThrow(PairingIsNotReadable::class);
});

it('N1-R49 — material with no expiry at all is refused', function (): void {
    // The requirement is that pairing material expires, so material that never
    // does is not pairing material. Defaulting to some interval here would be
    // this app deciding how long another machine's invitation lasts.
    expect(fn(): Pairing => Pairing::read(material(expires: null), HowItWasRead::Typed, whenItIsRead()))
        ->toThrow(PairingIsNotReadable::class);
});

it('N1-R49 — an expiry spelled as a string is not an expiry', function (): void {
    // A format that takes both spellings has two spellings of one fact, and
    // the day a producer switches is the day every app that handled only the
    // other reports correct material as malformed.
    $said = (string) json_encode([
        'address' => A_STACKS_ADDRESS,
        'fingerprint' => A_STACKS_DIGEST,
        'expires' => (string) WHILE_IT_IS_GOOD,
    ]);

    expect(fn(): Pairing => Pairing::read($said, HowItWasRead::Scanned, whenItIsRead()))
        ->toThrow(PairingIsNotReadable::class);
});

it('N1-R48 — material carrying a credential is refused', function (): void {
    // The requirement in the form somebody would actually violate it. Material
    // that hands the app a secret out of band is either a stack doing
    // something it must not, or a payload somebody else wrote.
    expect(fn(): Pairing => Pairing::read(
        material(also: ['credential' => 'hunter2']),
        HowItWasRead::Scanned,
        whenItIsRead(),
    ))->toThrow(PairingIsNotReadable::class);
});

it('N1-R48 — anything the format does not define is refused, whatever it is called', function (): void {
    // The reason the check is a closed set rather than a list of forbidden
    // names. A list is wrong the first time somebody picks a name nobody
    // thought of, and it fails open: the app pairs happily, having been handed
    // a secret. Three of these are credentials under names a refusal list
    // would plausibly miss.
    foreach (['bearer', 'admits', 'first_token', 'anything'] as $key) {
        expect(fn(): Pairing => Pairing::read(
            material(also: [$key => 'x']),
            HowItWasRead::Typed,
            whenItIsRead(),
        ))->toThrow(PairingIsNotReadable::class);
    }
});

it('N1-R48 — still reads material that says exactly what the format defines', function (): void {
    // The other half, and worth pinning: a closed set that refused correct
    // material would be found by an operator rather than by this suite.
    $paired = Pairing::read(material(), HowItWasRead::Scanned, whenItIsRead());

    expect($paired->at()->forTheClient())->toBe(A_STACKS_ADDRESS)
        ->and($paired->presenting()->forComparingByEye())->toBe(A_STACKS_DIGEST);
});

it('N1-R48 — a payload that is a list, not an object, is refused by name', function (): void {
    // A JSON array decodes to integer keys, and `[1, 2]` is a payload somebody
    // can send. It has to be refused like any other key the format does not
    // define, and the refusal has to be able to name it — which is why the key
    // is cast rather than assumed to be a string.
    expect(fn(): Pairing => Pairing::read('[1, 2]', HowItWasRead::Scanned, whenItIsRead()))
        ->toThrow(PairingIsNotReadable::class, 'carried "0"');
});

it('N1-R49 — an expiry before the epoch is refused by the type that knows why', function (): void {
    // Not checked twice. `Instant::atEpochSeconds()` refuses a moment before
    // the epoch and says so in its own words; a second check here would be the
    // same rule with a worse sentence, which is the reasoning `read()` already
    // gives for letting `Address` and `Fingerprint` do their own refusing.
    $said = (string) json_encode([
        'address' => A_STACKS_ADDRESS,
        'fingerprint' => A_STACKS_DIGEST,
        'expires' => -1,
    ]);

    expect(fn(): Pairing => Pairing::read($said, HowItWasRead::Typed, whenItIsRead()))
        ->toThrow(InstantIsBeforeTheEpoch::class);
});
