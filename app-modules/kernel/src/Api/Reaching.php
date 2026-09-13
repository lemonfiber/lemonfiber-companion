<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A way to reach one stack, built for that stack and nothing else.
 *
 * The port `ADR-0018` makes necessary. A client that could be built from an
 * address alone is a client that can be built for a machine nobody pinned, and
 * the whole trust model is that no such client exists: a stack is reached under
 * the certificate its pairing material promised, or it is not reached.
 *
 * So this takes a {@see Stack} rather than an {@see Address}. The four things a
 * stack holds travel together — the identity, the name, the address and the
 * pinned fingerprint — and taking the whole of it is what makes *"reach this
 * address, unpinned"* a sentence with no spelling here.
 *
 * **The session is separate, and asked for separately.** `N1-R7` exchanges the
 * credential for a session once and does not re-send it; `N1-R23` and `N4-R5`
 * keep the session out of anything retained. A stack is retained. Carrying the
 * session inside one would put the thing that must be persisted and the thing
 * that must not in a single value, which is the argument {@see Stack} already
 * makes about itself.
 */
interface Reaching
{
    /**
     * A client for this stack, held to the certificate it was introduced under.
     *
     * Answers the client as an object rather than a named type, because the
     * type is the SDK's and `kernel` may not name it — that is `A7`, and it is
     * the rule that keeps every capability testable without a network. What a
     * caller does with the object is call the SDK's own methods on it, which
     * only the adapter and the modules that already depend on the SDK can do.
     */
    public function client(Stack $stack, Session $session): object;
}
