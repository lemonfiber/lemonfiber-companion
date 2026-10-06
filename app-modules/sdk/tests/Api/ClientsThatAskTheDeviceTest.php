<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;

use function mb_strtolower;

use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Sdk\Api\ClientsThatAskTheDevice;
use Modules\Sdk\Api\PinnedClients;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\ADeviceOnANetwork;
use Tests\Support\Fakes\ALocalNetworkThat;
use Tests\Support\Fakes\NotesKeptInMemory;
use Throwable;

// A stack that went silent, told apart from a phone with no way to it.

function aStackOnTheShelf(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('s', Nonce::SHORTEST))),
        StackName::of('The shelf'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

function silence(): Unreachable
{
    return Unreachable::whenAsking('/api/status', 'Connection timed out');
}

/** What these clients say stood in the way, on a phone that answers like this. */
function whatThePhoneMakesOf(Throwable $why, ADeviceOnANetwork $network, ALocalNetworkThat $localNetwork): KindOfObstacle
{
    return new ClientsThatAskTheDevice(new PinnedClients(), $network, $localNetwork, new NotesKeptInMemory())
        ->whatStoodInTheWay(aStackOnTheShelf(), $why)
        ->kind();
}

it('reads silence on a phone with no network as the phone having none', function (): void {
    expect(whatThePhoneMakesOf(silence(), ADeviceOnANetwork::withNothingToReachOver(), ALocalNetworkThat::refusesIt()))
        ->toBe(KindOfObstacle::DeviceHasNoNetwork);
});

it('reads silence where the platform refused the app the local network as that refusal', function (): void {
    expect(whatThePhoneMakesOf(silence(), ADeviceOnANetwork::connected(), ALocalNetworkThat::refusesIt()))
        ->toBe(KindOfObstacle::LocalNetworkIsNotPermitted);
});

it('reads silence the phone does not explain as the stack\'s', function (): void {
    expect(whatThePhoneMakesOf(silence(), ADeviceOnANetwork::connected(), ALocalNetworkThat::letsItThrough()))
        ->toBe(KindOfObstacle::StackDidNotAnswer);
});

it('asks the platform about the stack\'s own address', function (): void {
    $localNetwork = ALocalNetworkThat::letsItThrough();
    whatThePhoneMakesOf(silence(), ADeviceOnANetwork::connected(), $localNetwork);

    expect($localNetwork->asked())->toBe(['https://192.168.1.42:8443']);
});

it('asks the phone nothing about a reach that was answered', function (Throwable $why, KindOfObstacle $met): void {
    $network = ADeviceOnANetwork::withNothingToReachOver();
    $localNetwork = ALocalNetworkThat::refusesIt();

    expect(whatThePhoneMakesOf($why, $network, $localNetwork))->toBe($met)
        ->and($network->timesAsked())->toBe(0)
        ->and($localNetwork->asked())->toBe([]);
})->with([
    'an envelope in another API version' => [ApiVersionMismatch::between(spoken: 1, answered: 2), KindOfObstacle::VersionsDisagree],
    'an answer it could not read' => [UnreadableResponse::notAnEnvelope(), KindOfObstacle::StackDidNotAnswer],
]);

it('hands on the client pinned to the stack', function (): void {
    $clients = new ClientsThatAskTheDevice(new PinnedClients(), ADeviceOnANetwork::connected(), ALocalNetworkThat::letsItThrough(), new NotesKeptInMemory());
    $reaches = $clients->client(aStackOnTheShelf(), Session::of('a-session-not-a-secret'))->baseUrl();

    expect($reaches->toString())->toBe('https://192.168.1.42:8443')
        ->and($reaches->pin()?->toString())->toBe(str_repeat('d', Fingerprint::CHARACTERS));
});

it('hands on the client pinned to the stack towards a path, asking the stack nothing of it', function (): void {
    $clients = new ClientsThatAskTheDevice(new PinnedClients(), ADeviceOnANetwork::connected(), ALocalNetworkThat::letsItThrough(), new NotesKeptInMemory());
    $reaches = $clients->towards(aStackOnTheShelf(), Session::of('a-session-not-a-secret'), Ability::of(Api::STATUS_ENDPOINT))->baseUrl();

    expect($reaches->toString())->toBe('https://192.168.1.42:8443')
        ->and($reaches->pin()?->toString())->toBe(str_repeat('d', Fingerprint::CHARACTERS));
});

/** What these clients note of a reach that ended this way, for a stack at this address. */
function whatIsNotedOf(Throwable $why, string $address): string
{
    $notes = new NotesKeptInMemory();
    $stack = Stack::of(
        StackId::of(Nonce::of(str_repeat('s', Nonce::SHORTEST))),
        StackName::of('The shelf'),
        Address::of($address),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );

    new ClientsThatAskTheDevice(new PinnedClients(), ADeviceOnANetwork::connected(), ALocalNetworkThat::letsItThrough(), $notes)
        ->whatStoodInTheWay($stack, $why);

    return implode("\n", $notes->noted());
}

it('notes what was raised, what the transport reported, the port and which kind of address, and never the address', function (): void {
    $noted = whatIsNotedOf(
        Unreachable::whenAsking('/api/status', 'php_network_getaddresses: getaddrinfo for Wessels-MacBook-Pro.local failed: No address associated with hostname'),
        'https://Wessels-MacBook-Pro.local:8443',
    );

    expect($noted)->toBe(sprintf('reach: nothing answered (%s), name_not_found, port 8443, address local_name', Unreachable::class));
    expect(mb_strtolower($noted))->not->toContain('wessels-macbook-pro');
});

it('notes a numeric address by its kind, and not its numbers', function (): void {
    $noted = whatIsNotedOf(silence(), 'https://192.168.1.42:8443');

    expect($noted)->toBe(sprintf('reach: nothing answered (%s), timed_out, port 8443, address numeric', Unreachable::class));
    expect($noted)->not->toContain('192.168');
});

it('notes nothing of a reach that was answered', function (): void {
    expect(whatIsNotedOf(ApiVersionMismatch::between(spoken: 1, answered: 2), 'https://192.168.1.42:8443'))->toBe('');
});
