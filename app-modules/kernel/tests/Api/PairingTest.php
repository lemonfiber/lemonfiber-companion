<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function dataset;
use function expect;
use function implode;
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
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;

use function sprintf;
use function str_repeat;
use function str_replace;
use function str_split;

use Tests\Support\Fakes\FrozenClock;

const A_STACKS_DIGEST = '3b8c1f09a7d24e6b5c0f81a2d93e47b6c8150af2937d6e4b1c05a8f39d27e64b';
const A_STACKS_ADDRESS = 'https://stack.local';
const A_STACKS_OWN_NAME_FOR_ITSELF = '7f3c9a1e5b2d4086a9c1e3f5b7d90246';

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
    ?string $stack = A_STACKS_OWN_NAME_FOR_ITSELF,
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

    if ($stack !== null) {
        $said['stack'] = $stack;
    }

    return (string) json_encode([...$said, ...$also]);
}

/** A clock stopped at the moment these tests happen. */
function whenItIsRead(int $at = NOW): FrozenClock
{
    return FrozenClock::at(Instant::atEpochSeconds($at));
}

it('carries the fingerprint the stack will present', function (): void {
    // The fingerprint comes from this material and never from the
    // network. A fingerprint learned from the connection it is meant to
    // validate proves nothing — somebody carried this across the gap, and the
    // gap is what makes it worth anything.
    $paired = Pairing::read(material(), HowItWasRead::Scanned, whenItIsRead());

    expect($paired->at()->forTheClient())->toBe(A_STACKS_ADDRESS)
        ->and($paired->presenting()->is(
            Fingerprint::of(A_STACKS_DIGEST),
        ))->toBeTrue();
});

it('reads the same material whichever way it arrived', function (): void {
    // Both routes, one parser. A code read by camera is not more trusted than
    // one read by a person, and a parser that branched here would be two
    // parsers of which only one would stay tested.
    $scanned = Pairing::read(material(), HowItWasRead::Scanned, whenItIsRead());
    $typed = Pairing::read(material(), HowItWasRead::Typed, whenItIsRead());

    expect($scanned->at()->is($typed->at()))->toBeTrue()
        ->and($scanned->presenting()->is($typed->presenting()))->toBeTrue();
});

it('remembers which way it arrived', function (): void {
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

it('names the machine as the stack names itself', function (): void {
    $said = Pairing::read(material(), HowItWasRead::Scanned, whenItIsRead());

    expect($said->stack()->is(StackId::saidBy(A_STACKS_OWN_NAME_FOR_ITSELF)))->toBeTrue();
});

it('refuses material that does not say which machine it is for', function (): void {
    // Without it the app cannot tell a machine it holds from a new one, and
    // pairing the same machine twice would put it on the list twice.
    expect(fn(): Pairing => Pairing::read(material(stack: null), HowItWasRead::Scanned, whenItIsRead()))
        ->toThrow(PairingIsNotReadable::class, 'read by scanned carried no stack')
        ->and(fn(): Pairing => Pairing::read(material(stack: '  '), HowItWasRead::Typed, whenItIsRead()))
        ->toThrow(PairingIsNotReadable::class, 'read by typed carried no stack');
});

it('refuses material whose stack is not a string', function (): void {
    expect(fn(): Pairing => Pairing::read(material(stack: null, also: ['stack' => 42]), HowItWasRead::Scanned, whenItIsRead()))
        ->toThrow(PairingIsNotReadable::class, 'read by scanned carried no stack');
});

it('refuses material naming its stack in a shape no stack mints', function (): void {
    // Read into the identifier's own refusal rather than the envelope's, the
    // way a malformed address or digest is: the key is there, and what it
    // holds is not an identifier.
    expect(fn(): Pairing => Pairing::read(material(stack: 'the-loft'), HowItWasRead::Typed, whenItIsRead()))
        ->toThrow(StackIsUnidentified::class, 'the 32 lower-case hexadecimal characters a stack mints');
});

it('reads material in the exact form a stack writes it', function (): void {
    $digest = sprintf('5adc06f2%s63aea', str_repeat('0', 51));
    $written = sprintf(
        '{"address":"https://the-loft.local:8443","fingerprint":"%s","expires":1790621817,"stack":"5e1d0a7b3c9f4e2d8a6b1c0f9e8d7c6b"}',
        $digest,
    );

    $said = Pairing::read($written, HowItWasRead::Scanned, whenItIsRead());

    expect($said->stack()->stored())->toBe('5e1d0a7b3c9f4e2d8a6b1c0f9e8d7c6b')
        ->and($said->presenting()->forComparingByEye())->toBe($digest);
});

dataset('the quotation marks a keyboard types for a straight one', [
    'an opening one' => ["\u{201C}"],
    'a closing one' => ["\u{201D}"],
    'a low one' => ["\u{201E}"],
    'an opening guillemet' => ["\u{00AB}"],
    'a closing guillemet' => ["\u{00BB}"],
    'a full-width one' => ["\u{FF02}"],
]);

it('reads material whose quotation marks a keyboard curled', function (string $curled): void {
    $typed = str_replace('"', $curled, material());

    $said = Pairing::read($typed, HowItWasRead::Typed, whenItIsRead());

    expect($said->at()->forTheClient())->toBe(A_STACKS_ADDRESS)
        ->and($said->presenting()->is(Fingerprint::of(A_STACKS_DIGEST)))->toBeTrue();
})->with('the quotation marks a keyboard types for a straight one');

it('reads material broken across lines and spaced out as the material the stack wrote', function (): void {
    // Line breaks fall inside the values as well as between them, the way a
    // screen wraps a long line and a paste keeps the wrapping.
    $pasted = sprintf(" \t%s\u{00A0}\r\n", implode("\n", str_split(material(), 16)));

    $said = Pairing::read($pasted, HowItWasRead::Typed, whenItIsRead());

    expect($said->at()->forTheClient())->toBe(A_STACKS_ADDRESS)
        ->and($said->presenting()->is(Fingerprint::of(A_STACKS_DIGEST)))->toBeTrue()
        ->and($said->stack()->stored())->toBe(A_STACKS_OWN_NAME_FOR_ITSELF);
});

it('refuses material that is not text as material that is not readable', function (): void {
    expect(fn(): Pairing => Pairing::read(sprintf("\xFF%s", material()), HowItWasRead::Typed, whenItIsRead()))
        ->toThrow(PairingIsNotReadable::class);
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

it('material promising a certificate for an unencrypted address is refused', function (): void {
    // The material contradicts itself. The fingerprint is "the
    // certificate that address will present", and an `http://` address presents
    // none — so the digest would be pinned against a connection with nothing to
    // compare it to, and "validate every subsequent connection against
    // it" could never be kept for this stack.
    //
    // Refused here rather than at the first connection, which is the only
    // other place it can be caught: `BaseUrl::pinned()` raises a configuration
    // problem from inside the transport, by which point the stack is written
    // down and the operator has been told they are paired. This is the one
    // moment the material is in front of somebody who can go and get better
    // material.
    expect(fn(): Pairing => Pairing::read(
        material(address: 'http://192.168.1.42'),
        HowItWasRead::Scanned,
        whenItIsRead(),
    ))->toThrow(PairingIsNotReadable::class);
});

it('refuses it for what it is, rather than as a malformed address', function (): void {
    // `http://192.168.1.42` is a perfectly good address and `Address` accepts
    // it. What is wrong is the pair: this address, with that fingerprint. So
    // the refusal has to name the contradiction rather than send somebody to
    // look for a typo in something that has none.
    $said = 'nothing was refused at all';

    try {
        Pairing::read(material(address: 'http://192.168.1.42'), HowItWasRead::Typed, whenItIsRead());
    } catch (PairingIsNotReadable $refused) {
        $said = $refused->getMessage();
    }

    // Every clause of the sentence, because the sentence is the refusal. A
    // message built in pieces is a message a mutation can take a piece out of
    // while the assertions still pass — which is exactly what this one did
    // until it became a single literal.
    expect($said)->toContain('unencrypted address')
        ->and($said)->toContain('presents no certificate')
        ->and($said)->toContain('fingerprint')
        ->and($said)->toContain('typed');
});

it('says which failure is worth simply retrying', function (): void {
    // "Try again" is advice somebody has already taken if they typed sixty-four
    // characters. A camera can be re-pointed; a transcription needs showing
    // what did not parse.
    expect(HowItWasRead::Scanned->isWorthSimplyRetrying())->toBeTrue()
        ->and(HowItWasRead::Typed->isWorthSimplyRetrying())->toBeFalse();
});

it('material that has expired is refused', function (): void {
    // A photograph of a QR code in somebody's camera roll is a durable
    // instruction to trust a host. It outlives the evening it was useful for,
    // and whoever picks the phone up later can act on it.
    expect(fn(): Pairing => Pairing::read(material(expires: NOW - 1), HowItWasRead::Scanned, whenItIsRead()))
        ->toThrow(PairingIsSpent::class);
});

it('material expiring at this very second is spent', function (): void {
    // A boundary that admits the exact second is a boundary two clocks
    // disagree about, and the stack's clock is not this phone's.
    expect(fn(): Pairing => Pairing::read(material(expires: NOW), HowItWasRead::Typed, whenItIsRead()))
        ->toThrow(PairingIsSpent::class);
});

it('says the code is stale rather than that it is wrong', function (): void {
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

it('material with no expiry at all is refused', function (): void {
    // The requirement is that pairing material expires, so material that never
    // does is not pairing material. Defaulting to some interval here would be
    // this app deciding how long another machine's invitation lasts.
    expect(fn(): Pairing => Pairing::read(material(expires: null), HowItWasRead::Typed, whenItIsRead()))
        ->toThrow(PairingIsNotReadable::class);
});

it('an expiry spelled as a string is not an expiry', function (): void {
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

it('material carrying a credential is refused', function (): void {
    // The requirement in the form somebody would actually violate it. Material
    // that hands the app a secret out of band is either a stack doing
    // something it must not, or a payload somebody else wrote.
    expect(fn(): Pairing => Pairing::read(
        material(also: ['credential' => 'hunter2']),
        HowItWasRead::Scanned,
        whenItIsRead(),
    ))->toThrow(PairingIsNotReadable::class);
});

it('anything the format does not define is refused, whatever it is called', function (): void {
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

it('still reads material that says exactly what the format defines', function (): void {
    // The other half, and worth pinning: a closed set that refused correct
    // material would be found by an operator rather than by this suite.
    $paired = Pairing::read(material(), HowItWasRead::Scanned, whenItIsRead());

    expect($paired->at()->forTheClient())->toBe(A_STACKS_ADDRESS)
        ->and($paired->presenting()->forComparingByEye())->toBe(A_STACKS_DIGEST);
});

it('a payload that is a list, not an object, is refused by name', function (): void {
    // A JSON array decodes to integer keys, and `[1, 2]` is a payload somebody
    // can send. It has to be refused like any other key the format does not
    // define, and the refusal has to be able to name it — which is why the key
    // is cast rather than assumed to be a string.
    expect(fn(): Pairing => Pairing::read('[1, 2]', HowItWasRead::Scanned, whenItIsRead()))
        ->toThrow(PairingIsNotReadable::class, 'carried "0"');
});

it('an expiry before the epoch is refused by the type that knows why', function (): void {
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
