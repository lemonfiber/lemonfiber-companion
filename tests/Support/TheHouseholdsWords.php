<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_any;
use function array_find;
use function array_map;
use function array_slice;
use function count;
use function explode;
use function in_array;
use function is_array;

use Lemonfiber\Sdk\Generated\HouseholdWords;

use function mb_strtolower;
use function preg_match;
use function preg_replace;
use function preg_split;
use function rtrim;
use function str_contains;
use function str_replace;
use function strlen;
use function substr;

/**
 * Whether a piece of text is in the household's words, against the one list the core keeps for every surface.
 *
 * What a member's surface may not show: a service's name, a domain term, a
 * size, a rate, an address or a version. The list is the core's, as the SDK
 * generates it.
 */
final readonly class TheHouseholdsWords
{
    /**
     * @param list<string> $names
     * @param list<string> $terms
     * @param list<string> $sizes
     * @param list<string> $marks
     */
    private function __construct(private array $names, private array $terms, private array $sizes, private array $marks) {}

    /** The list as the SDK generates it. */
    public static function read(): self
    {
        return new self(self::listed(HouseholdWords::NAMES), self::listed(HouseholdWords::TERMS), self::listed(HouseholdWords::SIZES), self::listed(HouseholdWords::MARKS));
    }

    /** How many names, terms, sizes and marks it holds, so a list that read as nothing is caught. */
    public function size(): int
    {
        return count($this->names) + count($this->terms) + count($this->sizes) + count($this->marks);
    }

    /** What in `$text` a member may not be shown, or null where it is all the household's words. */
    public function unhoused(string $text): ?string
    {
        $lower = mb_strtolower($text);

        return $this->termIn($lower) ?? $this->figureIn($lower) ?? $this->markIn($lower);
    }



    /** The first name or term said among the words of `$lower`. */
    private function termIn(string $lower): ?string
    {
        $words = preg_split("/[^\\p{L}\\p{N}']+/u", $lower, flags: PREG_SPLIT_NO_EMPTY);
        $words = is_array($words) ? $words : [];

        return array_find([...$this->names, ...$this->terms], static fn(string $term): bool => self::said($words, $term));
    }

    /** The first version, address or size among the tokens of `$lower`. */
    private function figureIn(string $lower): ?string
    {
        $tokens = preg_split('/\s+/u', $lower, flags: PREG_SPLIT_NO_EMPTY);
        $tokens = is_array($tokens) ? array_map($this->bare(...), $tokens) : [];

        foreach ($tokens as $at => $token) {
            if ($this->versioned($token) || $this->sized($token, $this->bare(explode('/', $tokens[$at + 1] ?? '')[0]))) {
                return $token;
            }
        }

        return null;
    }

    /** Whether the token is a number written in a size, or followed by one. */
    private function sized(string $token, string $next): bool
    {
        $digits = rtrim($token, 'abcdefghijklmnopqrstuvwxyz');
        $unit = substr($token, strlen($digits));

        return $digits !== '' && preg_match('/^[\d.]+$/', $digits) === 1 && (in_array($unit, $this->sizes, strict: true) || in_array($next, $this->sizes, strict: true));
    }

    /** The first mark of an address or a rate written in `$lower`. */
    private function markIn(string $lower): ?string
    {
        return array_find($this->marks, static fn(string $mark): bool => str_contains($lower, $mark));
    }

    /**
     * @param  list<string> $listed
     * @return list<string>
     */
    private static function listed(array $listed): array
    {
        return array_map(static fn(string $one): string => str_replace('-', ' ', $one), $listed);
    }

    /** @param list<string> $words */
    private static function said(array $words, string $term): bool
    {
        $wanted = explode(' ', $term);
        $length = count($wanted);

        return array_any(
            $words,
            static fn(string $word, int $at): bool => array_slice($words, $at, $length) === $wanted,
        );
    }

    /** The token without what is not a letter or a digit at either end. */
    private function bare(string $token): string
    {
        return (string) preg_replace('/^[^\p{L}\p{N}]+|[^\p{L}\p{N}]+$/u', '', $token);
    }

    /** Whether the token reads as a version or an address: numbers joined by dots, two or more, with a `v` before them or not. */
    private function versioned(string $token): bool
    {
        $parts = explode('.', (string) preg_replace('/^v/', '', $token));

        return count($parts) >= 2 && ! array_any($parts, static fn(string $part): bool => preg_match('/^\d+$/', $part) !== 1);
    }
}
