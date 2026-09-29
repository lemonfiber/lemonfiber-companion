<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A store of what the phone keeps, asked to let go of all of it at once.
 *
 * Every store of kept data implements this beside its own port, and the
 * composition root registers each one, so that a phone whose key has gone can
 * clear everything it kept by asking one thing. What was sealed under the old
 * key cannot be opened under the new one, so it is cleared rather than tried
 * and thrown away row by row.
 *
 * A pairing and a session are not kept here and are not touched by it: they
 * live in the platform's secure storage and outlast the key.
 */
interface ForgetsEverythingKept
{
    /** Let go of everything this store keeps, for every stack, and say how much that was. */
    public function forgetEverything(): Forgotten;
}
