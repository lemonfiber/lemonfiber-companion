<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Dx\Api\ClientsThatReachNothing;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheVersionsSpoken;
use Modules\Sdk\Api\Clients;
use Modules\Sdk\Api\ClientsThatAskTheDevice;
use Modules\Sdk\Api\PinnedClients;
use Tests\Support\Fakes\ADeviceOnANetwork;
use Tests\Support\Fakes\ALocalNetworkThat;
use Tests\Support\Fakes\NotesKeptInMemory;

// The Clients contract beyond the kernel's port: what stood in the way of a
// reach that got no answer this app could read. `ReachingContractTest` holds
// the client each of these hands back; this holds what each says about a reach
// that ended without one.
//
// The device is asked only by the binding that ships, so the one it is handed
// here is a phone on a network that lets the app through: on such a phone,
// every implementation reads the reach the same way.

/** Named for this file: the root suites share one namespace (G10). */
function aStackThatWentQuiet(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('q', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/** @return array<string, array{Closure(): Clients}> */
dataset('every set of clients', [
    'the pinned clients' => [fn(): Clients => new PinnedClients()],
    'the stand-in' => [fn(): Clients => new ClientsThatReachNothing(new PinnedClients())],
    'the clients that ask the device' => [fn(): Clients => new ClientsThatAskTheDevice(
        new PinnedClients(),
        ADeviceOnANetwork::connected(),
        ALocalNetworkThat::letsItThrough(),
        new NotesKeptInMemory(),
    )],
]);

it('reads silence as a stack that did not answer', function (Clients $clients): void {
    expect($clients->whatStoodInTheWay(aStackThatWentQuiet(), Unreachable::whenAsking('/api/status', 'Connection timed out')))
        ->toEqual(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
})->with('every set of clients');

it('reads an envelope in another API version as the two versions disagreeing, naming both', function (Clients $clients): void {
    $met = $clients->whatStoodInTheWay(aStackThatWentQuiet(), ApiVersionMismatch::between(spoken: 1, answered: 2));

    expect($met)->toEqual(Obstacle::versionsDisagree(TheVersionsSpoken::between(answered: 2, spoken: 1)))
        ->and([$met->versionsSpoken()->answered(), $met->versionsSpoken()->spoken()])->toBe([2, 1]);
})->with('every set of clients');

it('reads an answer it could not read as a stack that did not answer', function (Clients $clients): void {
    expect($clients->whatStoodInTheWay(aStackThatWentQuiet(), UnreadableResponse::notAnEnvelope()))
        ->toEqual(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
})->with('every set of clients');
