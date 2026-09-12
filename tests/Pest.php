<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

use function sprintf;

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
 */
pest()->beforeEach(function (): void {
    Http::preventStrayRequests();
})->in('Feature', 'Templates', 'Contract', sprintf('%s/../native/tests', __DIR__));
