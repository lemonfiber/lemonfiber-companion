<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A version of the wire contract this app can read.
 *
 * In the kernel rather than in the SDK adapter, and `N1-R14` is the reason:
 * a change of transport — LAN today, an overlay later — must not change the
 * contract the app speaks. Which versions it reads is a fact about the app,
 * not about the socket it reached the stack over, so a second transport
 * arriving finds the answer already written rather than writing its own.
 *
 * **An enum rather than a list of integers**, so that the set and the check are
 * one thing. `tryFrom` is the whole of `N1-R13`'s first clause: a version this
 * does not name has no case, and an envelope carrying it cannot be read.
 *
 * Adding a case is how support for a wire version is declared, and it is the
 * only edit that declares it — the refusal below reads its sentence from
 * {@see self::cases()}, and nothing else names a number.
 */
enum WireVersion: int
{
    /** The contract as it stands. */
    case One = 1;
}
