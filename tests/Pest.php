<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Saloon\Http\Faking\MockClient;
use Tests\Support\OurCode;
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
 *
 * **Over every suite, read from `phpunit.xml`.** Four were named here and there
 * are eight, and the four left out included `app-modules/*\/tests` — which is
 * where every adapter that speaks to a stack is tested. An unmocked read there
 * raised Saloon's `FatalRequestException` rather than its `NoMockResponse`,
 * which is the difference between being stopped and having dialled (`R4`).
 *
 * Saloon's half is the one that reaches every suite. `preventStrayRequests()`
 * resolves a factory out of the container, so it can only be arranged where the
 * application is booted — which is the same four directories `TestCase` is
 * extended into above, and is where the `Http` facade can be reached from at
 * all.
 */
pest()->beforeEach(function (): void {
    MockClient::global([]);
})->in(...OurCode::testDirectories());

pest()->beforeEach(function (): void {
    Http::preventStrayRequests();
})->in('Feature', 'Templates', 'Contract', sprintf('%s/../native/tests', __DIR__));
