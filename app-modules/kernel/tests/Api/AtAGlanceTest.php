<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_diff;
use function array_unique;
use function count;
use function dechex;
use function expect;
use function it;
use function mb_str_split;
use function mb_strtoupper;
use function mb_substr;

use Modules\Kernel\Api\AtAGlance;
use Modules\Kernel\Api\Fingerprint;

use function range;
use function sprintf;
use function str_repeat;
use function str_replace;

const ONE_CERTIFICATE = '3b8c1f09a7d24e6b5c0f81a2d93e47b6c8150af2937d6e4b1c05a8f39d27e64b';
const ANOTHER_CERTIFICATE = 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90';

function glanceAt(string $digest): string
{
    return AtAGlance::of(Fingerprint::of($digest))->shown();
}

it('N1-R51 — is short enough to check at a glance', function (): void {
    // Sixteen characters in four groups. Thirty-two is not a glance, and
    // sixty-four is what ADR-0018 rejects by name — the version where people
    // check the first four and the last four and then press accept.
    $shown = glanceAt(ONE_CERTIFICATE);

    expect(str_replace('-', '', $shown))->toHaveLength(16)
        ->and(mb_str_split($shown, 5))->toHaveCount(4);
});

it('N1-R51 — is derived from the whole fingerprint', function (): void {
    // The half truncation fails. The leading characters of a SHA-256 are
    // grindable, so a comparison an attacker can satisfy by matching a prefix
    // is not a comparison. Changing the *last* byte must change what is shown.
    $tail = sprintf('%sc', mb_substr(ONE_CERTIFICATE, 0, 63));

    expect(glanceAt($tail))->not->toBe(glanceAt(ONE_CERTIFICATE));
});

it('N1-R51 — changing the first byte changes it too', function (): void {
    $head = sprintf('c%s', mb_substr(ONE_CERTIFICATE, 1));

    expect(glanceAt($head))->not->toBe(glanceAt(ONE_CERTIFICATE));
});

it('N1-R51 — two different certificates do not share one', function (): void {
    expect(glanceAt(ONE_CERTIFICATE))->not->toBe(glanceAt(ANOTHER_CERTIFICATE));
});

it('N1-R51 — an anagram of a digest folds differently', function (): void {
    // The reason the fold is position-weighted. Without the index in the mix,
    // two bytes swapping places would produce the same groups — and a digest
    // is exactly the kind of thing somebody can rearrange.
    $swapped = '8c3b1f09a7d24e6b5c0f81a2d93e47b6c8150af2937d6e4b1c05a8f39d27e64b';

    expect(glanceAt($swapped))->not->toBe(glanceAt(ONE_CERTIFICATE));
});

it('is the same every time for the same certificate', function (): void {
    // Obvious and worth pinning: an operator comparing what two screens show
    // is comparing two runs of this.
    expect(glanceAt(ONE_CERTIFICATE))->toBe(glanceAt(ONE_CERTIFICATE));
});

it('does not read the same whichever case the digest arrived in', function (): void {
    // `Fingerprint` normalises case on the way in, so this follows — and it is
    // the property that makes the comparison survive a stack that prints its
    // digest in capitals.
    expect(glanceAt(ONE_CERTIFICATE))->toBe(glanceAt(mb_strtoupper(ONE_CERTIFICATE)));
});

it('uses no character somebody would misread', function (): void {
    // No I, O, 1 or 0. Somebody reading four groups off a screen and typing
    // them into a phone confuses those before anything else, and a comparison
    // that fails on a misread character teaches the operator that failures here
    // are noise.
    expect(glanceAt(ONE_CERTIFICATE))->not->toContain('I')
        ->and(glanceAt(ONE_CERTIFICATE))->not->toContain('O')
        ->and(glanceAt(ONE_CERTIFICATE))->not->toContain('1')
        ->and(glanceAt(ONE_CERTIFICATE))->not->toContain('0');
});

it('compares two glances without a screen doing it', function (): void {
    $one = AtAGlance::of(Fingerprint::of(ONE_CERTIFICATE));
    $same = AtAGlance::of(Fingerprint::of(ONE_CERTIFICATE));
    $other = AtAGlance::of(Fingerprint::of(ANOTHER_CERTIFICATE));

    expect($one->is($same))->toBeTrue()
        ->and($one->is($other))->toBeFalse();
});

it('spreads across the alphabet rather than settling', function (): void {
    // A fold that clustered would show the same few characters for every
    // certificate, which is a comparison that passes by accident. Checked over
    // a spread of digests rather than argued for in a comment.
    $seen = [];

    foreach (range(0, 15) as $at) {
        $digest = str_repeat(dechex($at), 64);
        $seen = [...$seen, ...mb_str_split(str_replace('-', '', glanceAt($digest)))];
    }

    expect(count(array_unique($seen)))->toBeGreaterThan(8);
});

it('uses every letter its alphabet declares, and no other', function (): void {
    // The alphabet is the single source of truth for its own size. It was not:
    // `HOW_MANY_LETTERS = 32` sat beside it, which is the same fact twice, and
    // the copy drifts the first time somebody removes a character they have
    // decided is also confusable. Shrink the alphabet and the fold indexes past
    // the end; grow it and the letters past the thirty-second are never chosen,
    // weakening the spread with nothing to say so.
    //
    // Pinned over a spread of digests rather than argued for: every character
    // this produces must be one the alphabet actually contains.
    $alphabet = mb_str_split('23456789ABCDEFGHJKLMNPQRSTUVWXYZ');
    $used = [];

    foreach (range(0, 15) as $at) {
        $used = [...$used, ...mb_str_split(str_replace('-', '', glanceAt(str_repeat(dechex($at), 64))))];
    }

    expect(array_diff(array_unique($used), $alphabet))->toBe([]);
});
