<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use Modules\Dx\Internal\WhatTheWireWouldAnswer;
use Modules\Kernel\Api\Reaching;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Sdk\Api\PinnedClients;

/**
 * A client for a stack that is not running, built out of the real one.
 *
 * What makes this worth having rather than a fake bound at the port: the client
 * handed back *is* the application's client. It was built by the one file
 * allowed to build one, it carries the pin that stack's pairing material
 * promised, and every check the SDK makes on the way back — the `api_version`
 * comparison, the required `kind`, the shape the envelope declares — is made.
 * Only the socket is missing.
 *
 * A fake bound at `Reaching` would skip all of that, and all of that is the
 * part most likely to be wrong. A screen rendering against such a fake has
 * proved the screen works. A screen rendering against this has proved the
 * screen, the reader and the contract agree, which is the question worth asking
 * on a device.
 *
 * **It builds no client of its own, which is why the pinning rule is untouched.**
 * `N1-R20` allows exactly one file to open a connection and
 * `NothingReachesAStackUnpinnedTest` enforces it by refusing any other file
 * that names the SDK's transport. This one asks {@see PinnedClients} for a
 * client and hands the result on, so the count of files that can reach a stack
 * is still one — and the client it decorates was pinned before it got here.
 *
 * **Nothing can leave the device once it has.** Saloon answers a mocked request
 * from the mock and never opens a connection; a request with no matching entry
 * raises rather than falling through to the network. {@see
 * WhatTheWireWouldAnswer} registers one catch-all, so there is no request this
 * can be asked for that reaches a socket and none that raises.
 */
final readonly class ClientsThatReachNothing implements Reaching
{
    public function __construct(private PinnedClients $pinned) {}

    /**
     * The port's `object` rather than the SDK's client type.
     *
     * The real adapter narrows this and rector is right to ask it to — that
     * file may name the SDK. This one may not, for the reason the class
     * docblock gives: naming the transport is what `N1-R20` reads to decide a
     * file can open a connection, and the whole argument for this class is that
     * it cannot. So the port's own spelling, which says as much as this file is
     * allowed to say.
     */
    public function client(Stack $stack, Session $session): object
    {
        $client = $this->pinned->client($stack, $session);

        // Attached to the connector rather than passed at construction,
        // because the constructor is the one thing this class is not allowed
        // to call. Saloon holds the mock on the connector and consults it
        // before the sender, so a request written after this line is answered
        // from the contract and the sender is never reached.
        $client->connector()->withMockClient(WhatTheWireWouldAnswer::toEverything());

        return $client;
    }
}
