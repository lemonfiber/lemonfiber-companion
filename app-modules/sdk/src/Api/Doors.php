<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Admission;
use Modules\Kernel\Api\Stack;

/**
 * A way to open one stack's door, built for that stack and nothing else.
 *
 * {@see Clients} for the reading half, and this is the other: `N1-R7` exchanges
 * a credential for a session, once, and the exchange has a transport of its own.
 * The SDK calls it {@see Admission} and it is a separate object from the client,
 * with a separate pin and a separate connector.
 *
 * **It is an interface for the reason `Clients` is.** {@see Admissions} built
 * its own door inline — `Admission::at(...)` in the middle of the method that
 * offers the password — so nothing could be put in front of it. Every screen of
 * this application can be drawn against a stand-in and the sign-in screen could
 * not: the one flow carrying the operator's password was the one flow that
 * reached the network whatever the switch said.
 *
 * **And it is what `N1-R20` now reads.** `Admission` offers `at()` and
 * `onPort()`, and the second builds a door with no pin at all. That was true
 * before this existed and nothing refused it — the rule listed the client's
 * transport and not the door's — so a file could have opened an unpinned door
 * carrying somebody's password and no rule would have said a word.
 */
interface Doors
{
    /**
     * A door for this stack, held to the certificate it was introduced under.
     *
     * Takes a {@see Stack} rather than an address and a digest, for the reason
     * {@see \Modules\Kernel\Api\Reaching} takes one: the four things a stack
     * holds travel together, and taking the whole of it is what makes *"open
     * this address, unpinned"* a sentence with no spelling here.
     */
    public function door(Stack $stack): Admission;
}
