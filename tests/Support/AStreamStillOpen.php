<?php

declare(strict_types=1);

namespace Tests\Support;

use function sprintf;
use function str_repeat;
use function strlen;

/**
 * What a stream still open after the given lines sends, up to the read that finds nothing.
 *
 * The SDK reads a stream {@see ONE_READ} bytes at a time and learns it ended
 * only on the read after the last byte. A body of exactly one read ends in a
 * read that comes back empty, which is what a stream still open between two
 * lines looks like. The padding is an event-stream comment, which carries
 * nothing.
 */
final readonly class AStreamStillOpen
{
    /** How many bytes the SDK reads off a stream at a time. */
    public const int ONE_READ = 8192;

    public static function after(string $said): string
    {
        return sprintf("%s:%s\n", $said, str_repeat(' ', self::ONE_READ - strlen($said) - 2));
    }
}
