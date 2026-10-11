<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use const SODIUM_BASE64_VARIANT_ORIGINAL;

use function sodium_bin2base64;

/**
 * Artwork held in memory on the device, for an image element to draw from.
 *
 * A picture is handed over under an opaque key and the stack it came from, and
 * an image element whose source is that key draws it. Nothing here or on
 * either native half writes a picture anywhere but memory.
 */
final readonly class Artwork
{
    private const string HELD = 'in-memory';

    private const string LET_GO = 'let-go';

    /** Hold a picture under its key, for the stack it came from, answering whether it is held. */
    public function hold(string $key, string $stack, string $bytes): bool
    {
        return WhatTheBridgeAnswered::to(Call::ArtworkHold, [
            'key' => $key,
            'stack' => $stack,
            'bytes' => sodium_bin2base64($bytes, SODIUM_BASE64_VARIANT_ORIGINAL),
        ])->outcome() === self::HELD;
    }

    /** Whether a picture is still held under its key. */
    public function holds(string $key): bool
    {
        return WhatTheBridgeAnswered::to(Call::ArtworkHolds, ['key' => $key])->says(WhatAnAnswerHolds::Holds);
    }

    /** Let go of every picture held for a stack, answering whether the device did. */
    public function letGoOf(string $stack): bool
    {
        return WhatTheBridgeAnswered::to(Call::ArtworkLetGo, ['stack' => $stack])->outcome() === self::LET_GO;
    }
}
