<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\Nonce;

use function random_bytes;
use function sodium_bin2hex;

/**
 * The platform's own source of randomness, which is the one thing this knows.
 *
 * `random_bytes` rather than `rand` or `uniqid`: the first is the operating
 * system's cryptographic source and the other two are predictable from an
 * observed value, which is exactly what a replayed command needs.
 *
 * `B2` scopes those calls to this module by path, so this is the far end of a
 * rule written before the thing it permits — the same arrangement `SystemClock`
 * sits at.
 *
 * Hex rather than raw bytes because the value travels in a header and through
 * a log, and a byte string survives neither. Sixteen bytes is thirty-two hex
 * characters, comfortably past the floor `Nonce` sets.
 *
 * Encoded with `sodium_bin2hex` rather than `bin2hex`, which the analyser
 * refuses here: the plain one is not constant-time, and its timing is a
 * function of the bytes it was given. That matters for exactly the kind of
 * value this is.
 */
final readonly class SystemEntropy implements Entropy
{
    /** Sixteen bytes, which is thirty-two hex characters once encoded. */
    private const int BYTES = 16;

    public function nonce(): Nonce
    {
        return Nonce::of(sodium_bin2hex(random_bytes(self::BYTES)));
    }
}
