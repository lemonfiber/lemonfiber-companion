<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Templates', 'Contract');

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
})->in('Feature', 'Templates', 'Contract');
