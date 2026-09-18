<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Client;
use Modules\Kernel\Api\Reaching;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;

/**
 * {@see Reaching}, said in the one module allowed to name what comes back.
 *
 * The kernel's port answers `object`, because `A7` keeps the SDK's name out of
 * `kernel` and out of every capability. That is right and it is not enough: the
 * adapters in this module call the client's own methods, so an `object` would
 * have to be narrowed at eight call sites into a branch nothing can reach.
 *
 * So each of them used to take `PinnedClients` itself — the concrete adapter,
 * named in a constructor — and the cost of that was not visible until something
 * tried to stand in for it. A concrete final class is a seam nothing can get
 * into: {@see \Modules\Dx\Api\ClientsThatReachNothing} was bound at the kernel's
 * port, resolved correctly, and reached by nothing at all, because every adapter
 * that opens a connection was handed a client the composition root had built
 * with `new`. With stand-ins on, the application dialled the addresses of
 * machines that do not exist.
 *
 * This is the same promise narrowed rather than a second one: it extends the
 * port, so the binding the kernel names and the binding these adapters take are
 * one thing, and an adapter cannot be handed a client the application did not
 * resolve.
 */
interface Clients extends Reaching
{
    /**
     * A client for this stack, held to the certificate it was introduced under.
     *
     * Narrowed from the port's `object` to the SDK's own type. `modules/sdk` is
     * the one module whose manifest requires the SDK (`E3`), so this
     * is the one interface that may say what a client is.
     */
    public function client(Stack $stack, Session $session): Client;
}
