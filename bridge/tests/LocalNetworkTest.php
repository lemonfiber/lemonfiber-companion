<?php

declare(strict_types=1);

use Lemonfiber\Native\LocalNetwork;
use Native\Mobile\Testing\FakeBridge;

// The local-network probe's PHP face, driven through the real bridge call.
//
// The bridge name is written out as a literal, for `LinkTest`'s reason.

beforeEach(function (): void {
    FakeBridge::disable();
});

it('reads a forbidden answer as the platform refusing the way', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.LocalNetwork.Probe', ['outcome' => 'forbidden']);

    expect(new LocalNetwork()->refusesTheWayTo('192.168.1.42', 8443))->toBeTrue();

    $bridge->assertCalled('Lemonfiber.LocalNetwork.Probe');
});

it('asks about the host and port it was given', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.LocalNetwork.Probe', ['outcome' => 'open']);

    new LocalNetwork()->refusesTheWayTo('den.local', 8443);

    $bridge->assertCalled('Lemonfiber.LocalNetwork.Probe', static fn(mixed $params): bool => $params === ['host' => 'den.local', 'port' => 8443]);
});

it('reads an open answer as nothing refused', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.LocalNetwork.Probe', ['outcome' => 'open']);

    expect(new LocalNetwork()->refusesTheWayTo('192.168.1.42', 8443))->toBeFalse();
});

it('reads no answer, or one it does not know, as nothing refused', function (array $answered): void {
    // Every machine that is not a handset, and a shim from a newer build: the
    // app reports the stack as not answering, which is what it always did.
    FakeBridge::enable()->respondTo('Lemonfiber.LocalNetwork.Probe', $answered);

    expect(new LocalNetwork()->refusesTheWayTo('192.168.1.42', 8443))->toBeFalse();
})->with([
    'a word it does not know' => [['outcome' => 'probably_maybe']],
    'no envelope' => [['nothing_useful' => true]],
]);

it('reads no bridge at all as nothing refused', function (): void {
    FakeBridge::enable();

    expect(new LocalNetwork()->refusesTheWayTo('192.168.1.42', 8443))->toBeFalse();
});
