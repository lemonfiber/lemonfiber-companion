<?php

declare(strict_types=1);

use Lemonfiber\Native\Link;
use Modules\Device\Api\PlatformNetwork;
use Modules\Kernel\Api\Networking;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Fakes\ADeviceOnANetwork;

// The Networking contract, run against the adapter and against the fake.
//
// `G2`'s shape, and the promise is narrow because the port is: an answer about
// this device, of one shape, that says nothing about any stack. What both must
// agree on is that a connected device says so and a disconnected one says so —
// and, critically, that neither of them ever raises. A launch is not a place to
// throw: the operator opened an app.
//
// The adapter arm runs over a bridge scripted into `FakeBridge`, which
// intercepts `nativephp_call()` in-process — so it goes through the function
// name from the manifest, the JSON out and the decoding of the answer rather
// than through something built to resemble it.
//
// What is deliberately *not* asserted is what an absent bridge means. The
// adapter reads it as reachable and the fake has no such case, so that
// judgement belongs to `LinkTest` and to `LinkRule`'s own suites — a contract
// asserting it would either fail on the fake or be weakened to pass.

/**
 * The adapter, over a bridge scripted to answer one way.
 *
 * Named for this file: the root suites share one namespace, and two functions
 * of the same name are a fatal the moment both load (`G10`).
 */
function overALinkThatSays(string $outcome): PlatformNetwork
{
    FakeBridge::disable();
    FakeBridge::enable()->respondTo('Lemonfiber.Link.Status', ['outcome' => $outcome]);

    return new PlatformNetwork(new Link());
}

/** @return array<string, array{Networking}> */
dataset('every networking implementation that is connected', [
    'the platform' => [fn(): Networking => overALinkThatSays('reachable')],
    'the fake' => [fn(): Networking => ADeviceOnANetwork::connected()],
]);

/** @return array<string, array{Networking}> */
dataset('every networking implementation with nothing to reach over', [
    'the platform' => [fn(): Networking => overALinkThatSays('unreachable')],
    'the fake' => [fn(): Networking => ADeviceOnANetwork::withNothingToReachOver()],
]);

it('N1-R37 — says so where the device can reach a network', function (Networking $network): void {
    expect($network->isConnected())->toBeTrue();
})->with('every networking implementation that is connected');

it('N1-R37 — says so where there is nothing to reach over', function (Networking $network): void {
    // The answer the whole port exists for. A phone in flight mode and a
    // machine that is switched off produce the same silence at the socket, and
    // this is the one that can be settled without sending anything.
    expect($network->isConnected())->toBeFalse();
})->with('every networking implementation with nothing to reach over');

it('answers the same way twice, because a launch may ask more than once', function (Networking $network): void {
    expect($network->isConnected())->toBe($network->isConnected());
})->with('every networking implementation that is connected');
