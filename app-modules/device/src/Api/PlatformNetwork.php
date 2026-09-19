<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Lemonfiber\Native\Link;
use Modules\Kernel\Api\Networking;

/**
 * What the platform says about this device's network.
 *
 * `B3` puts a platform call behind an adapter and this is the whole of this
 * one: one question, asked once, with nothing else asked for.
 *
 * **The kind of connection is never fetched, rather than fetched and dropped.**
 * Both platforms report whether the link is wifi, cellular or ethernet, whether
 * it is metered, and whether Low Data Mode is on. None of it crosses the
 * bridge: the envelope has one field and it holds one of two words. Nothing
 * about the operator's device is ever reported, and a value held but not sent
 * is one commit away from being sent — so the narrowest thing that answers the
 * question is the only thing that exists to send.
 *
 * **Where the reading of silence lives.** A device whose platform cannot be
 * asked answers *reachable*, which is the opposite of the cautious direction
 * and the right one here. That decision is made twice and in neither of them is
 * it this class's: `LinkRule` makes it on the handset, where the platform
 * refused to answer, and {@see Link} makes it in PHP, where there is no bridge
 * at all. Both are tested where they are. This adapter has no branch of its own
 * because there is nothing left for it to decide.
 */
final readonly class PlatformNetwork implements Networking
{
    public function __construct(private Link $link) {}

    public function isConnected(): bool
    {
        return $this->link->isReachable();
    }
}
