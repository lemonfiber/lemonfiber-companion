<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * Whether the platform refuses this app the local network on the way to one address.
 *
 * The PHP face of lemonfiber's own probe. A platform that asks permission before
 * an app may reach the local network answers a refused one with the same silence
 * a switched-off machine does, and the remedies are opposite: one is a switch in
 * the phone's settings, the other is a cupboard. The platform knows which, and
 * this asks it.
 *
 * **Only a refusal is a yes here.** An answer that is not the refusal, a word
 * this build does not know, a malformed envelope, or no bridge at all reads as
 * *not refused*, so the app reports what it has always reported: the stack did
 * not answer. Reading silence as a refusal would send every desktop and every
 * test run to a settings app that has no such switch.
 */
final readonly class LocalNetwork
{
    /** The one word that means the platform refused. */
    private const string FORBIDDEN = 'forbidden';

    /** Whether the platform refuses this app the way to this host and port. */
    public function refusesTheWayTo(string $host, int $port): bool
    {
        return WhatTheBridgeAnswered::to(Call::LocalNetworkProbe, ['host' => $host, 'port' => $port])->outcome() === self::FORBIDDEN;
    }
}
