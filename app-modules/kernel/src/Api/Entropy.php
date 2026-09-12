<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Something nobody can guess, asked rather than taken.
 *
 * The second port, and the same argument as the first: randomness is a hidden
 * input, so an idempotency key built from `rand()` cannot be asserted on and
 * the test around it asserts nothing. Behind a port, "this command was sent
 * with that key" is a statement (B2).
 *
 * It answers a `Nonce` rather than bytes or an int, because every caller wants
 * the same thing — a value to identify one attempt by — and handing out bytes
 * would leave each of them deciding how long is long enough.
 */
interface Entropy
{
    /** A value nobody can guess, different every time. */
    public function nonce(): Nonce;
}
