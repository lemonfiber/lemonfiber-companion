<?php

declare(strict_types=1);

namespace Tests\Support;

use function file_get_contents;
use function is_string;
use function preg_match_all;
use function sprintf;
use function str_replace;
use function stripslashes;
use function substr_count;

/**
 * The title every PHP test in this repository is written under, and where it is.
 *
 * A title is the string an `it`, `test`, `arch` or `describe` call opens with,
 * in either quote and a `sprintf` format included, which is the sentence a
 * reader of the run reads. Read over every test file {@see Tree::testFiles()}
 * finds.
 *
 * The fixture families hold planted tests as text inside PHP that is not a
 * test file, so they are not among what is read.
 */
final readonly class TestTitles
{
    /** How a title is opened, read over the source rather than parsed. */
    private const string A_TITLE = <<<'REGEX'
        /^\s*(?:it|test|arch|describe)\(\s*(?:sprintf\(\s*)?(?:'(?<single>(?:[^'\\]|\\.)*)'|"(?<double>(?:[^"\\]|\\.)*)")/m
        REGEX;

    /**
     * Every title, against the file and line it is written on.
     *
     * @return array<string, string> `path:line` => title
     */
    public static function everyOne(): array
    {
        $found = [];

        foreach (Tree::testFiles() as $path) {
            $found = [...$found, ...self::inOneFile($path)];
        }

        return $found;
    }

    /**
     * The titles one file writes, against the line each is on.
     *
     * @return array<string, string>
     */
    public static function inOneFile(string $path): array
    {
        $source = file_get_contents($path);
        $source = is_string($source) ? $source : '';
        $found = [];

        if (preg_match_all(self::A_TITLE, $source, $titles, PREG_OFFSET_CAPTURE) === false) {
            return $found;
        }

        foreach ($titles[0] as $index => [, $offset]) {
            $line = substr_count($source, "\n", 0, $offset) + 1;
            $title = sprintf('%s%s', $titles['single'][$index][0], $titles['double'][$index][0]);
            $found[sprintf('%s:%d', str_replace(sprintf('%s/', Tree::root()), '', $path), $line)] = stripslashes($title);
        }

        return $found;
    }
}
