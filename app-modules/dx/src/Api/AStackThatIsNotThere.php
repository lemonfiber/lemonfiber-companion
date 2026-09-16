<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use Modules\Kernel\Api\Reaching;
use Modules\Sdk\Api\PinnedClients;

/**
 * A stack that answers everything and is not running.
 *
 * The first thing this module can stand in for, and the one that makes the rest
 * of the app reachable: every screen past the first is behind a stack that
 * answers, so a device with nothing to talk to can be walked through exactly
 * one frame of this application. With this in place it can be walked through
 * all of them.
 *
 * **What it does not stand in for.** Pairing. A device holding no pairing has
 * no stack to reach, so this is never asked for — which is why standing in for
 * a stack and standing in for having paired with one are two affordances and
 * not one. The second is the next class in this directory, and keeping them
 * apart means somebody can look at the first run *and* at what a paired device
 * does, rather than only ever at the second.
 *
 * @implements StandsIn<Reaching>
 */
final readonly class AStackThatIsNotThere implements StandsIn
{
    public function insteadOf(): string
    {
        return Reaching::class;
    }

    /**
     * Built per call, which is what the port it replaces promises.
     *
     * `N1-R11` wants a client per stack and `N1-R24` wants a session no longer
     * lived than the reach it was made for. The real binding is not a singleton
     * for both of those reasons, and a stand-in that was one would be a
     * difference between what is being looked at and what ships — which is the
     * whole thing this module exists not to be.
     */
    public function which(): Reaching
    {
        return new ClientsThatReachNothing(new PinnedClients());
    }
}
