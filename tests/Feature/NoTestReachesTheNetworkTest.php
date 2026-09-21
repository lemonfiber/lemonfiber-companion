<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Exceptions\NoMockResponseFoundException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

// `G3` — no test reaches the network, proved rather than arranged.
//
// `tests/Pest.php` stops both clients this application has, and the second one
// is the one that matters: every call to a stack goes through the SDK, which is
// Saloon, and `Http::preventStrayRequests()` does not know Saloon exists. Until
// this was probed, a test that forgot its mock opened a socket — the answer was
// `fopen(https://127.0.0.1:1/api/status): Connection refused`, which is the
// process having dialled and been turned away rather than having been stopped.
//
// What that costs is not hypothetical. The fixtures name `192.168.1.42`, which
// is a private address on whatever network the machine running the suite is on,
// and the one machine most likely to answer there is the stack the person
// writing the test owns. A suite that passes by talking to a real house is a
// suite that fails in CI for a reason nobody can reproduce — and, worse, one
// that can pass in CI too, because the address answers nothing there and the
// error is swallowed into whichever obstacle the adapter reports.
//
// A rule arranged and never watched fail is a rule nobody knows the shape of,
// so both halves are made to happen here.

/** A stack whose address nothing answers at, so a real attempt would refuse. */
function aStackNobodyIsListeningOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('9', Nonce::SHORTEST))),
        StackName::of('Nowhere'),
        // Port 1 on loopback. A mocked client never dials it, and a real one
        // comes back with a connection refused rather than a mock's refusal —
        // which is exactly how the two are told apart.
        Address::of('https://127.0.0.1:1'),
        Fingerprint::of(str_repeat('a', Fingerprint::CHARACTERS)),
    );
}

it('G3 — a stack read with no mock in front of it never reaches a socket', function (): void {
    // Caught rather than expected, and the class is what is compared. A
    // `toThrow` says only that the wrong thing happened; this says which — and
    // the difference between Saloon's refusal and a connection error is the
    // whole of what this case is about.
    $said = '';

    try {
        new PinnedClients()
            ->client(aStackNobodyIsListeningOn(), Session::of('a-session-not-a-secret'))
            ->read('/api/status');
    } catch (
        NoMockResponseFoundException|FatalRequestException|ApiVersionMismatch|RequestFailed|UnreadableResponse $why
    ) {
        // Every one this can honestly be, named rather than caught as a
        // `Throwable` — which `C6` refuses, and rightly: a misspelled method
        // would otherwise be reported as though the network had been reached.
        // The three from the SDK are what `read()` declares; they cannot happen
        // here, and naming them is what lets the two that can be told apart.
        $said = $why::class;
    }

    // The named refusal, and not a connection error. Saloon looks for a
    // response, finds the global mock holding none, and raises before it
    // reaches the socket — so what comes back names this suite's own
    // arrangement rather than somebody's network.
    expect($said)->toBe(NoMockResponseFoundException::class);
});

it('G3 — the facade is stopped as well, for the calls that do not go through the SDK', function (): void {
    // The half that was already here. Kept as a case rather than trusted,
    // because the two are arranged in the same `beforeEach` and a change to one
    // is a change to the file the other lives in.
    $said = '';

    try {
        Http::get('https://127.0.0.1:1/anything');
    } catch (RuntimeException $why) {
        $said = $why->getMessage();
    }

    expect($said)->toContain('Attempted request to [https://127.0.0.1:1/anything] without a matching fake');
});

// The reset that was not one.
//
// `MockClient::global()` is `??=` — handed a client when one is already
// installed, it keeps the old one and drops the array on the floor. So the
// `beforeEach` that looks like it empties the global mock did nothing whenever
// a suite had installed its own, and five contract suites do exactly that.
//
// These two cases are ordered and the order is the whole point: the first
// leaves a response nothing consumes, and the second asserts the mock it starts
// with is empty. Without the `destroyGlobal()` beside the `global()` the second
// finds the first one's leftovers and is answered instead of refused — which is
// how a test asserting it cannot reach the network passes by being handed a
// reply.
//
// Written here rather than left to the flake it was. Under `--parallel` the
// leak surfaced about one run in three, moved whenever a test file was added
// anywhere, and read as whichever branch happened to be open having caused it.

it('leaves a response behind, for the case below', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(['left' => 'behind'], 200)]);

    expect(MockClient::getGlobal()?->isEmpty())->toBeFalse();
});

it('G3 — starts with an empty global mock however the last test left it', function (): void {
    expect(MockClient::getGlobal())->not->toBeNull()
        ->and(MockClient::getGlobal()?->isEmpty())->toBeTrue();
});
