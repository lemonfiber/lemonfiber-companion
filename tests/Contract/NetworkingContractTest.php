<?php

declare(strict_types=1);

use Modules\Device\Api\PlatformNetwork;
use Modules\Kernel\Api\Networking;
use Tests\Support\Fakes\ADeviceOnANetwork;
use Tests\Support\Fakes\APlatformNetwork;

// The Networking contract, run against the adapter and against the fake.
//
// `G2`'s shape, and the promise is narrow because the port is: an answer about
// this device, of one shape, that says nothing about any stack. What both must
// agree on is that a connected device says so and a disconnected one says so —
// and, critically, that neither of them ever raises. A launch is not a place to
// throw: the operator opened an app.
//
// The adapter is driven against a hand-written stand-in for the platform's own
// facade, as every adapter here is. There is no network stack behind a PHP
// process on a laptop, and without one the adapter is a file nothing executes.
//
// What is deliberately *not* asserted is what an absent bridge means. The
// adapter reads it as connected and the fake has no such case, so that
// judgement belongs to the adapter's own tests — a contract asserting it would
// either fail on the fake or be weakened to pass.

/** @return array<string, array{Networking}> */
dataset('every networking implementation that is connected', [
    'the platform' => [fn(): Networking => new PlatformNetwork(APlatformNetwork::connected())],
    'the fake' => [fn(): Networking => ADeviceOnANetwork::connected()],
]);

/** @return array<string, array{Networking}> */
dataset('every networking implementation with nothing to reach over', [
    'the platform' => [fn(): Networking => new PlatformNetwork(APlatformNetwork::withNothingToReachOver())],
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
