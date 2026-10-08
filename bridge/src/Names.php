<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use function filter_var;
use function is_string;

/**
 * What a machine's name resolves to, asked of the phone's own resolver.
 *
 * The PHP face of lemonfiber's own look-up. The app's runtime resolves names
 * through a resolver that does not answer a `.local` name, and the phone's
 * does, so a stack paired by its `.local` name is found nowhere without this.
 * The answer is the addresses to send to; the name stays the one paired with.
 *
 * **Only addresses are an answer.** A word this build does not know, an entry
 * that is not an address, a malformed envelope, or no bridge at all reads as
 * nothing found, so the app looks the name up the way it would without this
 * call. Nothing that came back is kept.
 */
final readonly class Names
{
    /**
     * The addresses a name resolves to that the app may send to, IPv4 first,
     * or none.
     *
     * @return list<string>
     */
    public function addressesOf(string $host): array
    {
        $answered = WhatTheBridgeAnswered::to(Call::Resolve, ['host' => $host]);

        if (WhatTheLookupFound::in($answered) !== WhatTheLookupFound::Found) {
            return [];
        }

        $addresses = [];

        foreach ($answered->listed(WhatAnAnswerHolds::Addresses) ?? [] as $address) {
            if (is_string($address) && filter_var($address, FILTER_VALIDATE_IP) !== false) {
                $addresses[] = $address;
            }
        }

        return $addresses;
    }
}
