<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use Lemonfiber\Sdk\Client;
use Modules\Dx\Internal\WhatTheWireWouldAnswer;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Sdk\Api\Clients;
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
 * A fake bound at the port would skip all of that, and all of that is the
 * part most likely to be wrong. A screen rendering against such a fake has
 * proved the screen works. A screen rendering against this has proved the
 * screen, the reader and the contract agree, which is the question worth asking
 * on a device.
 *
 * **It builds no client of its own, which is why the pinning rule is untouched.**
 * Exactly one file may open a connection, and
 * `NothingReachesAStackUnpinnedTest` enforces it by refusing any other file
 * that names the SDK's transport. This one asks {@see PinnedClients} for a
 * client and hands the result on, so the count of files that can reach a stack
 * is still one — and the client it decorates was pinned before it got here.
 *
 * **What it answers with depends on which machine it was handed.**
 * {@see AStandInStack} holds three, and the two that refuse are the point:
 * the obstacle screens are the ones an operator meets on a bad evening, and a
 * build where only the working machine is reachable is a build where they are
 * never looked at.
 *
 * **Nothing can leave the device once it has.** Saloon answers a mocked request
 * from the mock and never opens a connection; a request with no matching entry
 * raises rather than falling through to the network. {@see
 * WhatTheWireWouldAnswer} registers one catch-all, so there is no request this
 * can be asked for that reaches a socket and none that raises.
 */
final readonly class ClientsThatReachNothing implements Clients
{
    public function __construct(private PinnedClients $pinned) {}

    /**
     * The SDK's client, because {@see Clients} is what this stands in for.
     *
     * Naming the type is what the pinning rule used to read as *this file can open a
     * connection*, and this file cannot: the client comes from
     * {@see PinnedClients}, pinned, and is handed straight on.
     * `MAY_NAME_A_CLIENT` is where that distinction is written down, and the
     * rule beside it refuses this file the moment it names a way of building
     * one.
     */
    public function client(Stack $stack, Session $session): Client
    {
        $client = $this->pinned->client($stack, $session);

        // Attached to the connector rather than passed at construction,
        // because the constructor is the one thing this class is not allowed
        // to call. Saloon holds the mock on the connector and consults it
        // before the sender, so a request written after this line is answered
        // from the contract and the sender is never reached.
        //
        // Which machine it is decides what it answers with, and a stack this
        // module has never heard of behaves as the working one —
        // {@see AStandInStack} says why that, rather than a decision made here.
        $client->connector()->withMockClient(
            WhatTheWireWouldAnswer::asFarAs(AStandInStack::howAStackOfThisIdentityBehaves($stack->id())),
        );

        return $client;
    }
}
