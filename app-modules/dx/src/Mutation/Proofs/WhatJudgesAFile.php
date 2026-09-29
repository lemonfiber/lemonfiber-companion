<?php

declare(strict_types=1);

namespace Dx\Mutation\Proofs;

use function array_combine;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function basename;
use function implode;
use function ksort;
use function mb_strlen;
use function mb_strtolower;
use function preg_match;
use function sort;
use function sprintf;
use function str_ends_with;
use function str_replace;

/**
 * The tests a mutant of one file can be run against, read from the coverage map
 * the way Pest's mutation plugin reads it.
 *
 * The plugin runs each mutant against the tests that cover its line, named in
 * one `--filter` built from each covering test as `<Class>::(.*)<name>`. That
 * filter is a regular expression and it is not anchored, so it also selects any
 * test whose class name ends in the same letters — `ReachingTest` selects
 * `PairingReachingTest` too. And where the filter is longer than
 * `scripts/patch_pest_mutate.php` lets through, it is dropped and the mutant
 * runs against the whole suite. So a file is judged by every test file whose
 * class ends in the name of a test that covers it, or by every test where the
 * filter for all its covering tests together would not fit; where a covering
 * test cannot be placed in a file at all, that is every test too.
 *
 * What the map says covers each line is itself part of the answer: a proof
 * reads it through {@see linesOf()}, so a line whose covering tests changed is
 * judged again even where every file stayed the same.
 */
final readonly class WhatJudgesAFile
{
    /** Where `scripts/patch_pest_mutate.php` drops the filter and runs the whole suite. */
    public const int FILTER_CEILING = 100_000;

    /** How the plugin reads a test's class and name out of its id. */
    private const string PEST_READS = '/\\\\([a-zA-Z0-9]*)::(__pest_evaluable_)?([^#]*)"?/';

    /** @var array<string, string> every test file the map knows, by the lower-cased class name Pest gives it */
    private array $classes;

    /**
     * @param array<string, array<int, list<string>>> $lines the ids of the tests that run each line of each file
     * @param list<string>                            $tests every test file the map knows, relative to the repository
     */
    public function __construct(private array $lines, array $tests)
    {
        $this->classes = array_combine($tests, array_map(static fn(string $test): string => mb_strtolower(basename($test, '.php')), $tests));
    }

    /**
     * The test files a mutant of this file can run against, in path order, or
     * null where that is every test.
     *
     * @return list<string>|null
     */
    public function of(string $file): ?array
    {
        $filters = self::filtersFor($this->idsCovering($file));

        if (mb_strlen(sprintf('--filter="%s"', implode('|', array_keys($filters))), '8bit') >= self::FILTER_CEILING) {
            return null;
        }

        $judges = [];

        foreach (array_unique($filters) as $class) {
            $lower = mb_strtolower($class);
            $ending = array_keys(array_filter($this->classes, static fn(string $name): bool => str_ends_with($name, $lower)));

            if ($ending === []) {
                return null;
            }

            $judges = [...$judges, ...$ending];
        }

        $judges = array_values(array_unique($judges));
        sort($judges);

        return $judges;
    }

    /**
     * Each covered line of a file with the tests that cover it, one line of
     * text per line of code, in line order.
     */
    public function linesOf(string $file): string
    {
        $lines = array_key_exists($file, $this->lines) ? $this->lines[$file] : [];
        ksort($lines);
        $said = [];

        foreach ($lines as $line => $ids) {
            sort($ids);
            $said[] = sprintf('%d %s', $line, implode(' ', $ids));
        }

        return implode("\n", $said);
    }

    /** @return list<string> */
    private function idsCovering(string $file): array
    {
        $lines = array_key_exists($file, $this->lines) ? $this->lines[$file] : [];

        return array_values(array_unique(array_merge([], ...array_values($lines))));
    }

    /**
     * The filter the plugin writes for each test, with the class it names.
     *
     * A test the plugin cannot read a class out of is left out of its filter,
     * and so out of what the mutant is run against; it is left out here too.
     *
     * @param  list<string>          $ids
     * @return array<string, string>
     */
    private static function filtersFor(array $ids): array
    {
        $filters = [];

        foreach ($ids as $id) {
            if (preg_match(self::PEST_READS, $id, $found) !== 1) {
                continue;
            }

            $name = $found[2] === '__pest_evaluable_' ? str_replace(['__', '_'], ['.{1,2}', '.'], $found[3]) : $found[3];
            $filters[sprintf('%s::(.*)%s', $found[1], $name)] = $found[1];
        }

        return $filters;
    }
}
