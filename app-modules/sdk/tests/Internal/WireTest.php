<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\EnvelopeIsNotRead;
use Modules\Kernel\Api\WireVersion;
use Modules\Sdk\Internal\Wire;

// `doctor` is stood in for and not judged: `N1-R13` is answered before anything
// looks at the payload, so the body below is empty on purpose and a payload the
// contract would accept would have these two cases turn on the half they are
// not about. It is the version that is under test, and a stack ahead of its app
// is exactly the case where the body cannot be relied on to be readable.
//
// `doctor` and no other kind. The moment this file stands in for a second one,
// `G12` asks about that one on its own — an exemption that covered the file
// rather than the kind would be an exemption nobody could read.

it('N1-R13 — lets through an answer in a version this app reads', function (): void {
    $envelope = new Envelope(WireVersion::One->value, 'doctor', []);

    expect(Wire::checked($envelope))->toBe($envelope);
});

it('N1-R13 — refuses an answer in a version this app does not read', function (): void {
    // A stack ahead of its app is an ordinary state of the world — somebody
    // updated the machine and not the phone — so this is refused where the
    // answer arrives rather than discovered as a field whose meaning changed
    // three layers in, handed to a screen as a fact.
    expect(fn(): Envelope => Wire::checked(new Envelope(99, 'doctor', [])))
        ->toThrow(EnvelopeIsNotRead::class, 'version 99');
});
