<?php

declare(strict_types=1);

use Modules\Dx\Api\ClientsThatReachNothing;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Reaching;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Sdk\Api\PinnedClients;
use Tests\Support\Fakes\AClientForWhicheverStack;

// The Reaching contract, run against the adapter and against the fake.
//
// G2's shape. Every test that needs a client will hand its subject an
// `AClientForWhicheverStack` and never open a socket — so if the fake is easier
// to satisfy than the adapter, N1-R11 is being enforced against something that
// always says yes.
//
// What is asserted is only what both must promise. The adapter builds an SDK
// client and the fake does not, so "it speaks the API" belongs to the adapter's
// own tests: a contract asserting it would either fail on the fake or be
// weakened to pass, and a weakened contract is how a fake drifts.
//
// Nothing here connects. Both sides build a client and stop, which is the
// whole of what this port promises — an address is dialled when a request is
// made, not when a client is made.

const A_CERTIFICATE = '3b8c1f09a7d24e6b5c0f81a2d93e47b6c8150af2937d6e4b1c05a8f39d27e64b';

/** Named for this file: the root suites share one namespace (G10). */
function aStackToReach(string $at = 'https://192.168.1.42:8443', string $seed = 'a'): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of($at),
        Fingerprint::of(A_CERTIFICATE),
    );
}

function aSessionToCarry(): Session
{
    return Session::of('a-session-not-a-secret');
}

dataset('every way of reaching a stack', [
    'the pinned client' => [fn(): Reaching => new PinnedClients()],
    'the fake' => [fn(): Reaching => new AClientForWhicheverStack()],
    // The stand-in is here for the reason the fake is, and it matters more:
    // it is the one implementation somebody will be looking at a device
    // through, so a promise it quietly broke would be a promise broken in the
    // one place it is hardest to notice. It hands back the adapter's own
    // client with the socket taken out, so it should satisfy every clause the
    // adapter does — and if it ever does not, the divergence is the bug.
    'the stand-in' => [fn(): Reaching => new ClientsThatReachNothing(new PinnedClients())],
]);

it('builds something for a stack it was introduced to', function (Reaching $reaching): void {
    expect($reaching->client(aStackToReach(), aSessionToCarry()))->toBeObject();
})->with('every way of reaching a stack');

it('N1-R11 — builds a separate client per stack', function (Reaching $reaching): void {
    // The last clause, at the transport. A client holds one stack's pin, so two
    // stacks sharing one is how a reading comes to be attributed to the wrong
    // machine — and a fake that answered a shared instance would let an adapter
    // doing exactly that pass.
    $loft = $reaching->client(aStackToReach(), aSessionToCarry());
    $elsewhere = $reaching->client(aStackToReach('https://10.0.0.7:8443', 'b'), aSessionToCarry());

    expect($loft)->not->toBe($elsewhere);
})->with('every way of reaching a stack');

it('builds a separate client for the same stack asked for twice', function (Reaching $reaching): void {
    // Asked because the binding is not a singleton and the reason matters: a
    // client carries a session, and a session outliving the reach it was made
    // for is the shape `N1-R24` refuses — a confirmation served from a value
    // somebody else's request established.
    $once = $reaching->client(aStackToReach(), aSessionToCarry());
    $again = $reaching->client(aStackToReach(), aSessionToCarry());

    expect($once)->not->toBe($again);
})->with('every way of reaching a stack');
