<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A session, and when it stops being one.
 *
 * The pair {@see Admitted} carries on its opened arm, and it exists so that
 * arm can carry *both* without either being nullable. Holding them as two
 * `?Session`/`?Instant` fields would put a `?? throw` in `either()` for a state
 * the two named constructors cannot produce — unreachable code, which reads as
 * caution and is a line no test can defend.
 *
 * **`until` is here rather than inside {@see Session}.** A session is a secret
 * with one destination — a header — and nothing else. When it stops being valid
 * is not a secret, and is what a screen needs in order to tell `N1-R46`'s ended
 * session from `N1-R10`'s refused credential. Folding it in would give
 * `Session` a second reader, which is the whole mechanism
 * `AValueWithOneDestinationTest` exists to protect.
 */
final readonly class Opening
{
    public function __construct(public Session $session, public Instant $until) {}
}
