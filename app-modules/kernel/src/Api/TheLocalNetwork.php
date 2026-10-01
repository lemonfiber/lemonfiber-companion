<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Whether this platform refuses this app the local network on the way to a stack.
 *
 * `KindOfObstacle::LocalNetworkIsNotPermitted` is the kind it decides: a
 * platform that asks permission before an app reaches the local network answers
 * a refused one with the same silence a switched-off machine does, and the two
 * remedies are opposite. Asked only once a reach has met silence on a device
 * that has a network, so the question costs nothing on the way to a stack that
 * answers.
 *
 * **It answers about this device and never about the stack.** A refusal is
 * load-bearing; anything else, including a platform that cannot be asked, is
 * *not refused*, and the silence is reported as the stack's.
 */
interface TheLocalNetwork
{
    /** Whether the platform refuses this app the way to this address. */
    public function refusesTheWayTo(Address $at): bool;
}
