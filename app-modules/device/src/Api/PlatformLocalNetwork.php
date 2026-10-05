<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use function is_int;
use function is_string;

use Lemonfiber\Native\LocalNetwork;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\TheLocalNetwork;

use function parse_url;

use const PHP_URL_HOST;
use const PHP_URL_PORT;

/**
 * What the platform says about this app and the local network, on the way to one address.
 *
 * A platform call sits behind an adapter, and this is the whole of this one:
 * the address's host and port go to the bridge, and one word comes back. Where
 * the address names no port, it is the one its scheme does: every paired stack
 * is reached over `https`.
 *
 * **Where the reading of silence lives.** A platform that cannot be asked
 * answers *not refused*, decided by `LocalNetworkRule` on the handset and by
 * {@see LocalNetwork} in PHP. This adapter has no branch of its own beyond
 * reading the address.
 */
final readonly class PlatformLocalNetwork implements TheLocalNetwork
{
    /** The port an `https` address names without saying it. */
    private const int HTTPS = 443;

    public function __construct(private LocalNetwork $bridge) {}

    public function refusesTheWayTo(Address $at): bool
    {
        $host = parse_url($at->forTheClient(), PHP_URL_HOST);
        $port = parse_url($at->forTheClient(), PHP_URL_PORT);

        return is_string($host) && $this->bridge->refusesTheWayTo($host, is_int($port) ? $port : self::HTTPS);
    }
}
