<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\EnvelopeIsNotRead;
use Modules\Kernel\Api\WireVersion;

use function str_contains;

it('N1-R13 — names a version it reads, and does not name one it does not', function (): void {
    expect(WireVersion::tryFrom(1))->toBe(WireVersion::One);
    expect(WireVersion::tryFrom(2))->toBeNull();
});

it('N1-R13 — the refusal names the version that arrived', function (): void {
    // Half of what the requirement asks for, and the half an operator can act
    // on: "unsupported version" with no number is the message support threads
    // spend two replies establishing the facts of.
    expect(EnvelopeIsNotRead::inVersion(7)->getMessage())->toContain('version 7');
});

it('N1-R13 — the refusal names the versions this app reads, by reading them', function (): void {
    // The other half, and it is generated rather than typed. A case added to
    // `WireVersion` and not to a hand-written sentence would leave an operator
    // told their stack speaks a version this app cannot read, by a message that
    // no longer lists the version it had just started reading.
    $said = EnvelopeIsNotRead::inVersion(7)->getMessage();

    foreach (WireVersion::cases() as $version) {
        expect(str_contains($said, (string) $version->value))->toBeTrue();
    }
});

it('N1-R13 — names exactly one wire version, which is what exempts `newest()`', function (): void {
    // `newest()` carries a mutation exemption, and this is the assertion that
    // ends it. A set of one answers the same for the maximum, for the minimum
    // and for a sort read from either end, so nothing distinguishes the
    // selection from its opposite — and an exemption with no way to fall due
    // is an exemption that outlives its reason.
    expect(WireVersion::cases())->toHaveCount(
        1,
        'A second wire version now exists, so `newest()` is observable: with two '
        . 'cases the maximum and the minimum are different answers and a test can '
        . 'tell them apart. Delete the `@pest-mutate-ignore: MaxToMin` beside '
        . '`WireVersion::newest()` and write the test that pins the ordering — that '
        . 'the newest is the highest value, rather than whichever case happens to '
        . 'be declared first.',
    );
});
