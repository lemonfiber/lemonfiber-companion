<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use Lemonfiber\Sdk\Admission;
use Modules\Dx\Internal\WhatTheWireWouldAnswer;
use Modules\Kernel\Api\Stack;
use Modules\Sdk\Api\Doors;
use Modules\Sdk\Api\PinnedDoors;

/**
 * A door to a stack that is not running, built out of the real one.
 *
 * {@see ClientsThatReachNothing}'s argument at the other transport, and the one
 * that was missing: every screen of this application could be drawn against a
 * stand-in and the sign-in screen could not. `Admissions` built its own door
 * inline, so the one flow carrying the operator's password was the one flow
 * that reached the network whatever the switch said — with stand-ins on, a
 * sign-in dialled the address of a machine that does not exist.
 *
 * **It builds no door of its own.** {@see PinnedDoors} is asked for one, so it
 * is pinned before it arrives here and the count of files that can open a door
 * is still one. `MAY_NAME_A_CLIENT` in `NothingReachesAStackUnpinnedTest` is
 * where that distinction is written down, and the rule beside it refuses this
 * file the moment it names a way of building one.
 *
 * **What it answers with depends on which machine it was handed.**
 * {@see AStandInStack} holds three and one of them refuses the session — which
 * for a door is a refused password, and is the whole of `N1-R7`'s unhappy path.
 */
final readonly class DoorsThatOpenOnNothing implements Doors
{
    public function __construct(private PinnedDoors $pinned) {}

    public function door(Stack $stack): Admission
    {
        $door = $this->pinned->door($stack);

        // Attached to the connector, which is where Saloon looks before it
        // reaches the sender — so a request written after this line is answered
        // from the contract and no socket is opened. The door has a connector
        // of its own, separate from the client's, which is why standing in at
        // one of them left the other reaching the network.
        $door->connector()->withMockClient(
            WhatTheWireWouldAnswer::asFarAs(AStandInStack::howAStackOfThisIdentityBehaves($stack->id())),
        );

        return $door;
    }
}
