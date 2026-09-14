<?php

declare(strict_types=1);

namespace Modules\Device\Tests\Api;

use function expect;
use function it;

use Modules\Device\Api\PlatformNetwork;
use Tests\Support\Fakes\APlatformNetwork;

/** The adapter, over whatever the bridge said. Named for this file (`G10`). */
function whatItMadeOf(APlatformNetwork $said): bool
{
    return new PlatformNetwork($said)->isConnected();
}

it('reads a connected device as connected', function (): void {
    expect(whatItMadeOf(APlatformNetwork::connected()))->toBeTrue();
});

it('N1-R37 — reads a device with no network as having none', function (): void {
    // The one answer that decides anything. Everything below is about refusing
    // to produce this one by accident.
    expect(whatItMadeOf(APlatformNetwork::withNothingToReachOver()))->toBeFalse();
});

it('treats a bridge that is not there as connected', function (): void {
    // Every run of this suite and every desktop build. Reading an absent bridge
    // as *no network* would put those launches into a state whose remedy is
    // "turn your wifi on" — wrong, and nothing the operator can act on.
    expect(whatItMadeOf(APlatformNetwork::withNoBridge()))->toBeTrue();
});

it('treats an answer with no verdict in it as connected', function (): void {
    // A bridge that answered about the connection without saying whether there
    // is one. `C9` is why this is written out rather than coalesced: a `??`
    // would fold this into the case below and into the one above it.
    expect(whatItMadeOf(APlatformNetwork::answering('{"type":"wifi"}')))->toBeTrue();
});

it('treats a verdict of the wrong type as connected', function (): void {
    // `{"connected":"yes"}` is truthy, and a loose read would make it a network
    // on the strength of a string. It is not a shape this platform documents,
    // so what it means is unknown — and unknown is answered by trying.
    expect(whatItMadeOf(APlatformNetwork::answering('{"connected":"yes"}')))->toBeTrue();
});

it('does not read a null verdict as a missing network', function (): void {
    // The one that catches a `??` most quietly: `$said->connected ?? true` and
    // `is_bool()` agree here, and `(bool) $said->connected` does not.
    expect(whatItMadeOf(APlatformNetwork::answering('{"connected":null}')))->toBeTrue();
});
