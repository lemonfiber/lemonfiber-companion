<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use function array_key_exists;
use function is_array;
use function is_string;
use function json_decode;
use function nativephp_call;

/**
 * The phone's clock, as far as this plugin reads it: which zone it is set to.
 *
 * The PHP face of `ClockFunctions` in Kotlin and Swift. One call, and nothing
 * decided here beyond reading the answer: the name the platform gives, or an
 * empty one where nothing answered.
 *
 * **Off a handset the name is empty.** Every desktop and every test run has no
 * bridge, and an empty name is what lets whoever asked fall back to a zone of
 * their own choosing rather than to one this class invented.
 */
final readonly class Clock
{
    /** The zone's name in the time zone database, or empty where none was given. */
    public function zone(): string
    {
        $said = nativephp_call(Call::Zone->value, Call::CARRIES_NOTHING);

        if (! is_string($said)) {
            return '';
        }

        $decoded = json_decode($said, associative: true);

        if (! is_array($decoded) || ! array_key_exists('zone', $decoded)) {
            return '';
        }

        $zone = $decoded['zone'];

        return is_string($zone) ? $zone : '';
    }
}
