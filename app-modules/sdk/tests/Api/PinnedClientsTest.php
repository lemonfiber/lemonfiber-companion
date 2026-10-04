<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use Closure;

use function expect;
use function it;

use Lemonfiber\Sdk\Admission;
use Lemonfiber\Sdk\Client;
use Lemonfiber\Sdk\Exception\ConfigurationProblem;
use Lemonfiber\Sdk\Http\LemonfiberConnector;
use Lemonfiber\Sdk\Time\Duration;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Timeout;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\PinnedDoors;

use function str_repeat;

/** A certificate digest of the shape pairing material actually carries. */
const A_STACKS_DIGEST = '3b8c1f09a7d24e6b5c0f81a2d93e47b6c8150af2937d6e4b1c05a8f39d27e64b';

function aPairedStack(string $at = 'https://192.168.1.42:8443', string $digest = A_STACKS_DIGEST): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of($at),
        Fingerprint::of($digest),
    );
}

function aSession(): Session
{
    return Session::of('a-session-not-a-secret');
}

it('builds a client for a stack on the network, held to its certificate', function (): void {
    // The whole point of the adapter, and both halves of it are asked for
    // rather than the return type. A client for `192.168.1.42` is what the SDK
    // refused outright before ADR-0025 and now permits only where a pin was
    // supplied — so the address it carries is the stack's, and the digest the
    // handshake compares the peer against is the one the stack presents rather
    // than a default, an empty one, or a digest from somewhere else.
    //
    // Read off the address the client holds and the certificate it holds that
    // address to, which is everything the SDK sends through: it refuses a
    // request for anywhere else and applies no pin but this one. The
    // comparison happens while the connection is being set up, so nothing
    // observable after a request would tell a pinned client from an unpinned
    // one.
    $reaches = new PinnedClients()->client(aPairedStack(), aSession())->baseUrl();

    expect($reaches->toString())->toBe('https://192.168.1.42:8443')
        ->and($reaches->pin()?->toString())->toBe(A_STACKS_DIGEST);
});

it('takes the pin off the stack rather than from anywhere else', function (): void {
    // A digest the SDK will not accept proves the value reaching it came from
    // the stack: `Fingerprint` and `CertificatePin` agree on the shape, so a
    // stack the app holds is a stack this can build a client for, and nothing
    // in between could have substituted a digest of its own.
    $refused = null;

    try {
        new PinnedClients()->client(aPairedStack(digest: str_repeat('a', Fingerprint::CHARACTERS)), aSession());
    } catch (ConfigurationProblem $said) {
        $refused = $said;
    }

    expect($refused)->toBeNull('a digest the app accepts must be one the SDK accepts');
});

it('leaves an unencrypted address to the SDK to refuse', function (): void {
    // A pin compares against a certificate and plain HTTP presents none. Not
    // re-checked in the adapter: a second copy of that rule is a second place
    // for the two to disagree, and the SDK's refusal already names the scheme.
    $refused = null;

    try {
        new PinnedClients()->client(aPairedStack(at: 'http://192.168.1.42:8080'), aSession());
    } catch (ConfigurationProblem $said) {
        $refused = $said->getMessage();
    }

    expect($refused)->toBeString()
        ->and($refused)->toContain('http');
});

it('builds a separate client per stack, so no reading can be attributed to the wrong one', function (): void {
    // Two machines never mistaken for each other, at the transport. A client
    // holds one stack's pin, so two stacks must never share one — which is why
    // the binding is not a singleton and why this answers a new client each time.
    $clients = new PinnedClients();

    $loft = $clients->client(aPairedStack(), aSession());
    $elsewhere = $clients->client(aPairedStack(at: 'https://10.0.0.7:8443'), aSession());

    expect($loft)->not->toBe($elsewhere);
});

/**
 * How long a connection the SDK was handed waits for a call, in seconds.
 *
 * Read off the connector, which holds the wait every call it sends shares; a
 * call that waited it out would take the test that long.
 */
function howLongItWaits(Client|Admission $holdingAConnector): float
{
    $connector = Closure::bind(static fn(Client|Admission $held): LemonfiberConnector => $held->connector, null, $holdingAConnector::class)($holdingAConnector);

    return Closure::bind(static fn(LemonfiberConnector $held): Duration => $held->wait, null, LemonfiberConnector::class)($connector)->inSeconds();
}

it('gives every client and every door the wait the app keeps for a call to a stack', function (): void {
    $client = new PinnedClients()->client(aPairedStack(), aSession());
    $door = new PinnedDoors()->door(aPairedStack());

    expect(howLongItWaits($client))->toBe((float) Timeout::ordinary()->inSeconds())
        ->and(howLongItWaits($door))->toBe((float) Timeout::ordinary()->inSeconds());
});
