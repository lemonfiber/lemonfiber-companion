<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use function is_bool;

use Modules\Kernel\Api\Networking;
use Native\Mobile\Network as Platform;

use function property_exists;

/**
 * What the platform says about this device's network.
 *
 * `B3` puts a platform call behind an adapter and this is the whole of this
 * one: one question, asked of `Network.Status`, with everything else it answers
 * deliberately discarded.
 *
 * **The kind of connection is dropped on purpose.** The platform reports
 * whether the link is wifi, cellular or ethernet, whether it is metered, and
 * whether Low Data Mode is on. None of it is read. `N4-R12` keeps this app from
 * reporting anything about the operator's device, and a value held but not sent
 * is one commit away from being sent — so the narrowest thing that answers the
 * question is what crosses the boundary.
 *
 * **Absent is connected, and that is the safe direction here.** The facade
 * answers `null` where the bridge is not there, which on a handset means the
 * call failed and everywhere else means this is not a handset. Reading that as
 * *no network* would put a launch on every desktop and every test run into a
 * state whose remedy is *turn your wifi on*, which is wrong and unactionable.
 * Reading it as connected means the app tries, and a stack it cannot reach is
 * reported the way it always was — one attempt wasted, and the honest answer.
 */
final readonly class PlatformNetwork implements Networking
{
    public function __construct(private Platform $network) {}

    public function isConnected(): bool
    {
        $said = $this->network->status();

        if ($said === null) {
            return true;
        }

        // Written out rather than coalesced, which `C9` refuses: a `??` on a
        // property folds absent, present-and-null and present-and-the-wrong-
        // type into one answer, and the one it picks reads as *carry on*.
        if (! property_exists($said, 'connected') || ! is_bool($said->connected)) {
            return true;
        }

        return $said->connected;
    }
}
