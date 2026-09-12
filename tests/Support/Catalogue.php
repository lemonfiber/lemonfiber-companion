<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_map;
use function basename;
use function glob;

use const GLOB_ONLYDIR;

use function is_array;
use function is_string;
use function sprintf;

/**
 * One locale's translations for one module, read off disk.
 *
 * Every rule about text a person reads needs the same two things — which
 * locales exist, and what one of them says — and each one that fetched them for
 * itself had to name the `lang/` layout again. Two copies of "where the
 * catalogues are" disagree the day one moves, and the test that disagrees is
 * the one that quietly stops checking.
 *
 * Read rather than resolved through the translator: a rule about a missing key
 * cannot ask the thing whose job is to hide a missing key. Laravel looks one
 * up, misses, falls back, misses again and renders the key — so asking it would
 * turn every absence into a plausible-looking string.
 */
final readonly class Catalogue
{
    /**
     * Every locale the application ships, by the directory it lives in.
     *
     * Read off disk rather than listed here, so adding a language is adding a
     * directory rather than adding a directory and remembering this.
     *
     * @return list<string>
     */
    public static function locales(): array
    {
        $directories = glob(Tree::at('lang/*'), GLOB_ONLYDIR);

        return array_map(basename(...), $directories === false ? [] : $directories);
    }

    /**
     * One group's keys and sentences, flattened to dotted form.
     *
     * Flattened because a catalogue may nest — a group per enum reads better
     * than a prefix repeated on every row — and a rule asking whether a case
     * has a word should not have to know which of the two shapes was used.
     *
     * @return array<string, string>
     */
    public static function of(string $locale, string $group): array
    {
        /** @var mixed $rows */
        $rows = require Tree::at(sprintf('lang/%s/%s.php', $locale, $group));

        return self::flatten($group, $rows);
    }

    /**
     * Everything one locale says, every group flattened under its own name.
     *
     * The keys read as the translator sees them — `health.conclusion.fail` —
     * because a rule comparing two locales has to report the key somebody would
     * search for, and half a key sends them to the wrong file.
     *
     * @return array<string, string>
     */
    public static function all(string $locale): array
    {
        $said = [];

        foreach (Tree::filesUnder(Tree::at(sprintf('lang/%s', $locale)), '.php') as $file) {
            $said = [...$said, ...self::of($locale, basename($file, '.php'))];
        }

        return $said;
    }

    /** @return array<string, string> */
    private static function flatten(string $prefix, mixed $rows): array
    {
        if (is_string($rows)) {
            return [$prefix => $rows];
        }

        if (! is_array($rows)) {
            return [];
        }

        $found = [];

        foreach ($rows as $key => $nested) {
            $under = $prefix === '' ? (string) $key : sprintf('%s.%s', $prefix, $key);

            $found = [...$found, ...self::flatten($under, $nested)];
        }

        return $found;
    }
}
