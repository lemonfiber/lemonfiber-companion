<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_column;
use function max;

/**
 * A version of the wire contract this app can read.
 *
 * In the kernel rather than in the SDK adapter, and the reason is this:
 * a change of transport — LAN today, an overlay later — must not change the
 * contract the app speaks. Which versions it reads is a fact about the app,
 * not about the socket it reached the stack over, so a second transport
 * arriving finds the answer already written rather than writing its own.
 *
 * **An enum rather than a list of integers**, so that the set and the check are
 * one thing. `tryFrom` is the whole of the first clause: a version this
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
     *
     * **At one case no selection function is observable, this one included.**
     * A set of one answers the same for the maximum, for the minimum, for a
     * sort read from either end and for the first case: each of them returns
     * the only element there is. So no test can tell this line from its
     * opposite, and that is a fact about how large the set is rather than
     * about how the line is written — it stops holding the day a second wire
     * version is declared, and not before.
     */
    public static function newest(): self
    {
        // Exempt from the mutator that puts `min` where `max` is, because at
        // one case the two are the same program. The exemption removes itself:
        // `WireVersionTest` asserts that this enum holds exactly one case, so
        // the day a second arrives that test fails and names this line as the
        // one to delete.
        // @pest-mutate-ignore: MaxToMin
        return self::from(max(array_column(self::cases(), 'value')));
    }
}
