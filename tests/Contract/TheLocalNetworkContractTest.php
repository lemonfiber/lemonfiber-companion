<?php

declare(strict_types=1);

use Lemonfiber\Native\LocalNetwork;
use Modules\Device\Api\PlatformLocalNetwork;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\TheLocalNetwork;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Fakes\ALocalNetworkThat;

// The TheLocalNetwork contract, run against the adapter and against the fake.
//
// `NetworkingContractTest`'s shape: the adapter arm runs over a bridge scripted
// into `FakeBridge`. What an absent bridge means is `LocalNetworkTest`'s and
// `LocalNetworkRule`'s, for the reason `NetworkingContractTest` gives.

/** The adapter, over a bridge scripted to answer one way. */
function overAProbeThatSays(string $outcome): PlatformLocalNetwork
{
    FakeBridge::disable();
    FakeBridge::enable()->respondTo('Lemonfiber.LocalNetwork.Probe', ['outcome' => $outcome]);

    return new PlatformLocalNetwork(new LocalNetwork());
}

/** The stack the way is asked to. */
function aStackOnTheLocalNetwork(): Address
{
    return Address::of('https://192.168.1.42:8443');
}

/** @return array<string, array{TheLocalNetwork}> */
dataset('every local network that lets the app through', [
    'the platform' => [fn(): TheLocalNetwork => overAProbeThatSays('open')],
    'the fake' => [fn(): TheLocalNetwork => ALocalNetworkThat::letsItThrough()],
]);

/** @return array<string, array{TheLocalNetwork}> */
dataset('every local network that refuses the app', [
    'the platform' => [fn(): TheLocalNetwork => overAProbeThatSays('forbidden')],
    'the fake' => [fn(): TheLocalNetwork => ALocalNetworkThat::refusesIt()],
]);

it('says nothing is refused where the platform lets the app through', function (TheLocalNetwork $network): void {
    expect($network->refusesTheWayTo(aStackOnTheLocalNetwork()))->toBeFalse();
})->with('every local network that lets the app through');

it('says so where the platform refuses the app the local network', function (TheLocalNetwork $network): void {
    expect($network->refusesTheWayTo(aStackOnTheLocalNetwork()))->toBeTrue();
})->with('every local network that refuses the app');

it('asks the platform about the address\'s host and port, and the scheme\'s port where it names none', function (): void {
    FakeBridge::disable();
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.LocalNetwork.Probe', ['outcome' => 'open']);
    $network = new PlatformLocalNetwork(new LocalNetwork());

    $network->refusesTheWayTo(Address::of('https://den.local:8443'));
    $network->refusesTheWayTo(Address::of('https://den.local'));

    $bridge->assertCalled('Lemonfiber.LocalNetwork.Probe', static fn(mixed $params): bool => $params === ['host' => 'den.local', 'port' => 8443])
        ->assertCalled('Lemonfiber.LocalNetwork.Probe', static fn(mixed $params): bool => $params === ['host' => 'den.local', 'port' => 443]);
});

it('the fake names every address it was asked about', function (): void {
    $network = ALocalNetworkThat::refusesIt();
    $network->refusesTheWayTo(aStackOnTheLocalNetwork());

    expect($network->asked())->toBe(['https://192.168.1.42:8443']);
});
