<?php

declare(strict_types=1);

namespace Modules\Design\Api;

use function implode;
use function is_int;
use function json_encode;
use function mb_substr;
use function sprintf;

/**
 * A control's tap, written as the expression the package parses: a method of the screen drawing it, and what it is handed.
 *
 * A tap reaches the screen as text the package parses: the method up to the
 * first parenthesis, and its arguments as JSON once every single quote has
 * become a double one. A value written into that text as it stands is read as
 * part of the expression, so a name with a quote in it closes its own argument
 * and opens another. Here each string is written as JSON with every quote,
 * angle bracket and ampersand escaped, between single quotes: nothing in it
 * survives as a character the parse or the markup reads, and the parse hands
 * the method exactly the value written.
 */
final readonly class ATap
{
    /**
     * What JSON escapes in a string, so neither the parse nor the markup reads anything in it.
     *
     * Slashes and letters past ASCII are left as they are: neither reads them.
     */
    private const int ESCAPED = JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

    private function __construct(private string $written) {}

    /** A tap calling `$method`, handing it `$with`. */
    public static function calling(string $method, int|string ...$with): self
    {
        $arguments = [];

        foreach ($with as $value) {
            $arguments[] = self::argument($value);
        }

        return new self(sprintf('%s(%s)', $method, implode(', ', $arguments)));
    }

    /** The tap as the control is handed it, which the parse reads back as the method and each value as it is. */
    public function written(): string
    {
        return $this->written;
    }

    /** One value, as the parse reads it back. */
    private static function argument(int|string $value): string
    {
        return is_int($value) ? (string) $value : sprintf("'%s'", mb_substr(json_encode($value, self::ESCAPED), 1, -1));
    }
}
