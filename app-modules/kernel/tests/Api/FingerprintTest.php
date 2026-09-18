<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function get_class_methods;
use function it;
use function mb_strtoupper;

use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\FingerprintIsNotAFingerprint;

use function str_repeat;

const A_DIGEST = '3b8c1f09a7d24e6b5c0f81a2d93e47b6c8150af2937d6e4b1c05a8f39d27e64b';

it('takes a digest the length a certificate fingerprint is', function (): void {
    expect(Fingerprint::of(A_DIGEST)->is(Fingerprint::of(A_DIGEST)))->toBeTrue();
});

it('reads the two spellings of a digest as the same certificate', function (): void {
    // Hex has an upper and a lower spelling of every digest and they name the
    // same certificate. A comparison that called them different would refuse
    // the right machine — which is then reported as "this is not the
    // machine you were introduced to", about a machine that is.
    expect(Fingerprint::of(A_DIGEST)->is(Fingerprint::of(mb_strtoupper(A_DIGEST))))->toBeTrue();
});

it('is not another certificate', function (): void {
    expect(Fingerprint::of(A_DIGEST)->is(Fingerprint::of(str_repeat('a', 64))))->toBeFalse();
});

it('refuses anything that is not sixty-four characters, and says how many', function (): void {
    // A fingerprint of the wrong length can never match one, so pinning it
    // would make a stack that pairs successfully and is then unreachable
    // forever — with the failure arriving as a refused connection rather than
    // as the bad pairing code it was.
    expect(fn(): Fingerprint => Fingerprint::of(str_repeat('a', 63)))
        ->toThrow(FingerprintIsNotAFingerprint::class, 'carried 63');

    expect(fn(): Fingerprint => Fingerprint::of(''))
        ->toThrow(FingerprintIsNotAFingerprint::class, 'carried 0');
});

it('refuses sixty-four characters that are not hexadecimal', function (): void {
    expect(fn(): Fingerprint => Fingerprint::of(str_repeat('z', 64)))
        ->toThrow(FingerprintIsNotAFingerprint::class, 'hexadecimal');
});

it('says nothing about what arrived when it refuses', function (): void {
    // A malformed fingerprint is not a secret, but it arrives in the same
    // payload as one, and echoing part of a pairing payload into a stack trace
    // is a habit rather than a decision.
    expect(fn(): Fingerprint => Fingerprint::of(str_repeat('z', 64)))
        ->toThrow(FingerprintIsNotAFingerprint::class, 'Nothing was pinned.');
});

it('offers no way to show one to a person', function (): void {
    // `ADR-0018` rejects a human-read fingerprint by name: it asks somebody to
    // compare sixty-four hex characters across two screens, and they check the
    // first four and the last four. The comparison belongs in `is()`, and a
    // display accessor here would be the beginning of that screen.
    // `get_class_methods` rather than reflection: P4 forbids reflection here,
    // and the question is only which names exist.
    //
    // `forComparingByEye()` is named on this list deliberately, and it is not a
    // display accessor — it is the one input to `AtAGlance`, which folds the
    // whole digest into sixteen characters somebody will really compare. That
    // exists because the confirmation has no software comparison available: typed
    // pairing scanned nothing, so nothing carried a digest to compare against,
    // and what is left is the operator confirming.
    //
    // The two live apart so that the ability to display a fingerprint cannot be
    // reached *from* a fingerprint — getting one is an explicit act naming
    // `AtAGlance`, which is a line a reviewer sees. Adding a second reader here
    // fails this test, which is the point of pinning the list.
    expect(get_class_methods(Fingerprint::class))->toBe(['of', 'forComparingByEye', 'is']);
});
