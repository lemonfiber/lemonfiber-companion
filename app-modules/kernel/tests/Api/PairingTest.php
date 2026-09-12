<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function json_encode;

use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\FingerprintIsNotAFingerprint;
use Modules\Kernel\Api\HowItWasRead;
use Modules\Kernel\Api\Pairing;
use Modules\Kernel\Api\PairingIsNotReadable;

const A_STACKS_DIGEST = '3b8c1f09a7d24e6b5c0f81a2d93e47b6c8150af2937d6e4b1c05a8f39d27e64b';
const A_STACKS_ADDRESS = 'https://stack.local';

function material(?string $address = A_STACKS_ADDRESS, ?string $fingerprint = A_STACKS_DIGEST): string
{
    $said = [];

    if ($address !== null) {
        $said['address'] = $address;
    }

    if ($fingerprint !== null) {
        $said['fingerprint'] = $fingerprint;
    }

    return (string) json_encode($said);
}

it('N1-R18 — carries the fingerprint the stack will present', function (): void {
    // `ADR-0018`: the fingerprint comes from this material and never from the
    // network. A fingerprint learned from the connection it is meant to
    // validate proves nothing — somebody carried this across the gap, and the
    // gap is what makes it worth anything.
    $paired = Pairing::read(material(), HowItWasRead::Scanned);

    expect($paired->at()->forTheClient())->toBe(A_STACKS_ADDRESS)
        ->and($paired->presenting()->is(
            Fingerprint::of(A_STACKS_DIGEST),
        ))->toBeTrue();
});

it('N1-R6 — reads the same material whichever way it arrived', function (): void {
    // Both routes, one parser. A code read by camera is not more trusted than
    // one read by a person, and a parser that branched here would be two
    // parsers of which only one would stay tested.
    $scanned = Pairing::read(material(), HowItWasRead::Scanned);
    $typed = Pairing::read(material(), HowItWasRead::Typed);

    expect($scanned->at()->is($typed->at()))->toBeTrue()
        ->and($scanned->presenting()->is($typed->presenting()))->toBeTrue();
});

it('N1-R6 — remembers which way it arrived', function (): void {
    expect(Pairing::read(material(), HowItWasRead::Typed)->how())->toBe(HowItWasRead::Typed)
        ->and(Pairing::read(material(), HowItWasRead::Scanned)->how())->toBe(HowItWasRead::Scanned);
});

it('refuses something that is not material at all', function (): void {
    expect(fn(): Pairing => Pairing::read('not json', HowItWasRead::Scanned))
        ->toThrow(PairingIsNotReadable::class, 'did not carry an address');
});

it('names which half was missing, and how it was read', function (): void {
    // The message is for whoever reads the log, and the two halves mean
    // different things: material with no fingerprint is a stack that produced
    // it wrongly, not an operator who read it wrongly.
    expect(fn(): Pairing => Pairing::read(material(fingerprint: null), HowItWasRead::Typed))
        ->toThrow(PairingIsNotReadable::class, 'read by typed carried no fingerprint');

    expect(fn(): Pairing => Pairing::read(material(address: null), HowItWasRead::Scanned))
        ->toThrow(PairingIsNotReadable::class, 'read by scanned carried no address');
});

it('refuses a half that is present and empty', function (): void {
    // The case a coalesce would have turned into "absent" and this refuses by
    // name: the key is there, and there is nothing in it.
    expect(fn(): Pairing => Pairing::read(material(address: '   '), HowItWasRead::Typed))
        ->toThrow(PairingIsNotReadable::class, 'carried no address');
});

it('lets the address and the fingerprint refuse in their own words', function (): void {
    // Deliberately not wrapped. A malformed digest inside well-formed material
    // is `Fingerprint`'s refusal to explain, and wrapping it would replace a
    // message naming the problem with one naming the envelope.
    expect(fn(): Pairing => Pairing::read(material(fingerprint: 'nowhere near a digest'), HowItWasRead::Scanned))
        ->toThrow(FingerprintIsNotAFingerprint::class);
});

it('N1-R6 — says which failure is worth simply retrying', function (): void {
    // "Try again" is advice somebody has already taken if they typed sixty-four
    // characters. A camera can be re-pointed; a transcription needs showing
    // what did not parse.
    expect(HowItWasRead::Scanned->isWorthSimplyRetrying())->toBeTrue()
        ->and(HowItWasRead::Typed->isWorthSimplyRetrying())->toBeFalse();
});
