<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\EnvelopeIsNotRead;
use Modules\Kernel\Api\WireVersion;
use Modules\Sdk\Internal\Wire;

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
