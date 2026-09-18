<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use Modules\Sdk\Api\Clients;
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
 * **It replaces {@see Clients} rather than the kernel's port.** The kernel's
 * `Reaching` is the name a capability may use; every adapter that actually
 * opens a connection takes the narrowed one, because it calls the client's own
 * methods. Standing in at the kernel's port was resolved correctly and reached
 * by nothing at all — with stand-ins on, the application dialled the addresses
 * of machines that do not exist.
 *
 * @implements StandsIn<Clients>
 */
final readonly class AStackThatIsNotThere implements StandsIn
{
    public function insteadOf(): string
    {
        return Clients::class;
    }

    /**
     * Built per call, which is what the port it replaces promises.
     *
     * A client is wanted per stack, and a session no longer
     * lived than the reach it was made for. The real binding is not a singleton
     * for both of those reasons, and a stand-in that was one would be a
     * difference between what is being looked at and what ships — which is the
     * whole thing this module exists not to be.
     */
    public function which(): Clients
    {
        return new ClientsThatReachNothing(new PinnedClients());
    }
}
