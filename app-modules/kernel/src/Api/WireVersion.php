<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function usort;

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

    /**
     * The newest contract this build understands.
     *
     * For a diagnostic report, where the useful fact is what this app can read
     * rather than what it happens to have read. Somebody helping wants to know
     * whether a phone is behind the machine it is talking to, and the highest
     * version it names is the answer to that.
     *
     * **Derived from {@see self::cases()} rather than named**, which this
     * enum's whole design asks for: adding a case is how support for a version
     * is declared and is meant to be the only edit that declares it. A
     * `current()` returning `self::One` would be a second declaration, and the
     * day a second case arrives it would be the stale one.
     *
     * Not `current()`, which {@see Shape::current()} is called and which would
     * be the wrong name here. A shape is the one thing this build writes; a
     * wire version is a set this build reads, and *newest* is a fact about a
     * set where *current* would be a claim about a single value.
     */
    public static function newest(): self
    {
        $known = self::cases();
        usort($known, static fn(self $a, self $b): int => $b->value <=> $a->value);

        return $known[0];
    }
}
