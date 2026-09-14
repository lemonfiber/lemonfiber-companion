<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Whether this device is on a network at all.
 *
 * `N1-R37` wants a launch with no network told apart from one that could not
 * reach the stack, and `Obstacle::DeviceHasNoNetwork` has existed for it since
 * the obstacles were written without anything ever producing one — because
 * nothing could. A phone in flight mode and a machine that is switched off
 * produce the same silence at the socket, and the app was reporting both as the
 * machine.
 *
 * The difference matters more than it looks. A stack that did not answer sends
 * somebody to the cupboard to check a machine; no network at all is answered
 * where they are standing, in seconds, and they never need to think about the
 * stack. Telling them apart is the difference between a remedy and an errand.
 *
 * **It answers about this device and never about a stack.** A phone on wifi can
 * still fail to reach a machine that is asleep, on another network, or behind a
 * permission this app has not been granted — and this port says nothing about
 * any of those. It answers the one question that can be settled without sending
 * anything: is there a network here to send it over.
 *
 * **Asked before reaching, never instead of it.** A connected device is not a
 * reachable stack, so an affirmative here is permission to try rather than a
 * prediction that it will work. Only the refusal is load-bearing, which is why
 * this is worth asking at a launch and worth nothing as a substitute for one.
 */
interface Networking
{
    /**
     * Whether this device has a network connection of any kind.
     *
     * A boolean rather than the kind of connection, deliberately. The platform
     * says whether it is wifi, cellular or ethernet and whether it is metered,
     * and none of that is this app's business: `N4-R12` keeps it from reporting
     * anything about the operator's device, and a stack lives on a local
     * network — so *which* network is a question whose only honest answer is
     * the one attempt this port exists to let the app make.
     */
    public function isConnected(): bool;
}
