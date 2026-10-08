<?php

declare(strict_types=1);

/*
 * What a shard's verdict depends on, so a shard whose proof already passed is not run again.
 *
 * Part of `scripts/mutation.php`, which requires it.
 */

use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;

// What every shard's verdict reads beyond its own files, the tests that judge
// them and the files those tests run: what the vendor tree is and how it is
// patched, how the suite boots, and what a screen renders that no coverage
// map records — templates, translations, routes and configuration.
const WHAT_EVERY_VERDICT_READS = [
    'composer.lock',
    'phpunit.xml',
    'tests/Pest.php',
    'tests/TestCase.php',
    'tests/Support',
    'scripts/mutation.php',
    'scripts/mutation',
    'scripts/patch_pest_mutate.php',
    'scripts/patch_pest_mutate_shared_coverage.php',
    'bootstrap',
    'config',
    'lang',
    'resources',
    'routes',
    'bridge/composer.json',
    ':(glob)app-modules/*/composer.json',
    ':(glob)app-modules/*/resources/**',
    ':(glob)app-modules/*/config/**',
    ':(glob)app-modules/*/routes/**',
    ':(glob)app-modules/*/lang/**',
];

// Every test, for a shard a group judges: which of a group's tests judge which
// held line is not in the map, so every one of them is read.
const EVERY_TEST = [
    'tests',
    'bridge/tests',
    ':(glob)app-modules/*/tests/**',
];

/**
 * What a shard's verdict depends on, as one digest, or empty where that cannot
 * be told.
 *
 * A mutant is killed or not by the tests that run its line, against every line
 * those tests run, under the vendor tree and the files no coverage map records.
 * The digest is taken over the git blob of each of those files, so two commits
 * whose files agree have the same proof. Without a coverage map, what judges
 * the shard cannot be told, so nothing is claimed.
 *
 * @param array{label: string, floor: int, files: list<string>, held: list<array{floor: int, path: string, group: string}>} $shard
 */
function proofOf(string $root, array $shard): string
{
    $map = getenv('MUTATION_SHARED_COVERAGE');

    if (! is_string($map) || ! is_readable($map)) {
        return '';
    }

    $judging = $shard['held'] === [] ? whatJudges($root, coverageIn($map), $shard['files']) : EVERY_TEST;
    $read = blobsOf($root, [...$shard['files'], ...array_column($shard['held'], 'path'), ...$judging, ...WHAT_EVERY_VERDICT_READS]);

    return $read === '' ? '' : hash('sha256', sprintf("%d\n%s", $shard['floor'], $read));
}

/**
 * The git blob of every tracked file under these paths, one per line, in path
 * order.
 *
 * @param  list<string>  $paths
 */
function blobsOf(string $root, array $paths): string
{
    $specs = implode(' ', array_map(escapeshellarg(...), array_values(array_unique($paths))));
    $read = shell_exec(sprintf('git -C %s ls-files -s -- %s', escapeshellarg($root), $specs));

    return is_string($read) ? $read : '';
}

/**
 * The test files that run a line of these files, and every file those tests
 * run.
 *
 * @param  list<string>  $files
 * @return list<string>
 */
function whatJudges(string $root, ProcessedCodeCoverageData $data, array $files): array
{
    $indexes = testsRunning($data, $files, $root);

    return [...testFilesOf($root, $data, $indexes), ...filesRunBy($data, $indexes, $root)];
}

/**
 * The indexes of every test that runs a line of these files.
 *
 * @param  list<string>  $files
 * @return array<int, int>
 */
function testsRunning(ProcessedCodeCoverageData $data, array $files, string $root): array
{
    $wanted = array_flip($files);
    $indexes = [];

    foreach ($data->lineCoverage() as $file => $lines) {
        if (array_key_exists(relativeTo($root, $file), $wanted)) {
            $indexes += testsOnAnyOf($lines);
        }
    }

    return $indexes;
}

/**
 * The indexes of every test that ran any of these lines.
 *
 * @param  array<int, array<int, int>|null>  $lines
 * @return array<int, int>
 */
function testsOnAnyOf(array $lines): array
{
    $indexes = [];

    foreach ($lines as $hits) {
        foreach (array_keys($hits ?? []) as $index) {
            $indexes[$index] = $index;
        }
    }

    return $indexes;
}

/**
 * The files that hold the given tests, matched by name as {@see testsOf()}
 * matches them.
 *
 * @param  array<int, int>  $indexes
 * @return list<string>
 */
function testFilesOf(string $root, ProcessedCodeCoverageData $data, array $indexes): array
{
    $classes = array_flip(array_map(
        static fn(string $id): string => testKey(preg_replace('#^P\\\\#u', '', explode('::', $id, 2)[0]) ?? $id),
        array_values(array_intersect_key($data->testIds(), $indexes)),
    ));
    $listed = shell_exec(sprintf("git -C %s ls-files -- '*Test.php'", escapeshellarg($root)));

    return array_values(array_filter(
        explode("\n", is_string($listed) ? $listed : ''),
        static fn(string $path): bool => $path !== '' && array_key_exists(testKey(mb_substr($path, 0, -4)), $classes),
    ));
}

/** A path from the coverage map, relative to the repository. */
function relativeTo(string $root, string $file): string
{
    return str_starts_with($file, sprintf('%s/', $root)) ? mb_substr($file, mb_strlen($root) + 1) : $file;
}
