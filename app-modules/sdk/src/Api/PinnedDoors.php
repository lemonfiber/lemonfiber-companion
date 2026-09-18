<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Admission;
use Modules\Kernel\Api\Stack;

/**
 * The one place a door to a stack is opened.
 *
 * {@see PinnedClients} for the reading half and this for the exchange. The exchange
 * offers the operator's password once, and the offering has a transport of its
 * own — the SDK calls it {@see Admission}, with its own pin and its own
 * connector.
 *
 * **Pinned, with no unpinned spelling.** The SDK offers `at()` and `onPort()`,
 * and only the first is named here. `onPort()` is the loopback door, correct
 * for a surface running on the machine and wrong for every connection this app
 * makes — a phone is never on the machine. This is also the one request
 * carrying somebody's password, which makes it the last one that should ever
 * reach a peer whose identity nothing established.
 *
 * That argument used to live in {@see Admissions}, which built its own door in
 * the middle of the method that offers the password. The pinning rule never read it:
 * the rule listed the client's transport and not the door's, so a file could
 * have opened an unpinned door carrying a credential and nothing would have
 * said a word. The split is what makes the rule able to ask — one file builds a
 * door, and it is this one.
 *
 * It is also what makes the exchange standable-in-for. Every screen of this
 * application can be drawn against a stand-in and the sign-in screen could not:
 * the one flow carrying the password was the one flow that reached the network
 * whatever the switch said.
 */
final readonly class PinnedDoors implements Doors
{
    public function door(Stack $stack): Admission
    {
        return Admission::at(
            $stack->at()->forTheClient(),
            $stack->presents()->forComparingByEye(),
        );
    }
}
