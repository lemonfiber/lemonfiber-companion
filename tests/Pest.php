<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Saloon\Http\Faking\MockClient;
use Tests\TestCase;

// The native expansion's tests are here too, by path rather than by name: they
// live in `native/tests` because the plugin is a package, and `in()` resolves a
// bare word against this directory. They need the application because
// `FakeBridge` binds into the container — without it, the seam the whole file
// depends on cannot be reached and every assertion would be about a bridge that
// was never intercepted.
pest()->extend(TestCase::class)->in('Feature', 'Templates', 'Contract', sprintf('%s/../native/tests', __DIR__));

/*
 * G3 — no test reaches the network.
 *
 * Enforced rather than intended. An adapter test that quietly hits a real host
 * passes on a laptop with the stack running and fails in CI for a reason nobody
 * can reproduce; worse, it can pass in CI too and hide a broken fixture. This
 * makes a stray request an immediate, named failure.
 *
 * **Both clients, because this app has two and uses the second for everything.**
 * `preventStrayRequests()` is Laravel's and covers the `Http` facade. Every call
 * to a stack goes through the SDK, which is Saloon, and Saloon asked no
 * permission at all: a test that forgot its mock opened a socket. Probed rather
 * than assumed — an unmocked read answered
 * `fopen(https://127.0.0.1:1/api/status): Connection refused`, which is the
 * process having dialled. The fixtures name `192.168.1.42`, a private address
 * on whatever network the machine running the suite is on.
 *
 * A global mock holding no responses is the cure: Saloon looks for one, finds
 * none and raises `NoMockResponseFoundException` before it reaches a socket. A
 * test that wants a response installs its own over the top, which is what every
 * contract suite already does.
 */
pest()->beforeEach(function (): void {
    Http::preventStrayRequests();
    MockClient::global([]);
})->in('Feature', 'Templates', 'Contract', sprintf('%s/../native/tests', __DIR__));
