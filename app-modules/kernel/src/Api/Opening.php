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
 * **`whose` is here for the same reason `until` is.** Who a session belongs to is
 * not a secret, and it is what decides which application a person is given — so it
 * has a reader, and the whole point of keeping `Session` to one destination is that
 * it has none.
 *
 * **`until` is here rather than inside {@see Session}.** A session is a secret
 * with one destination — a header — and nothing else. When it stops being valid
 * is not a secret, and is what a screen needs in order to tell an ended
 * session from a refused credential. Folding it in would give
 * `Session` a second reader, which is the whole mechanism
 * `AValueWithOneDestinationTest` exists to protect.
 */
final readonly class Opening
{
    public function __construct(
        public Session $session,
        public Instant $until,
        public Whose $whose,
    ) {}
}
