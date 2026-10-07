<?php

declare(strict_types=1);

/*
 * The coverage map the tests job writes, read for which tests run which lines.
 *
 * Part of `scripts/mutation.php`, which requires it.
 */

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;

/** The coverage a map holds, in either shape the coverage library writes. */
function coverageIn(string $map): ProcessedCodeCoverageData
{
    /** @var array{basePath: string, codeCoverage: ProcessedCodeCoverageData}|CodeCoverage $loaded */
    $loaded = require $map;

    return is_array($loaded) ? $loaded['codeCoverage'] : $loaded->getData();
}

/**
 * The indexes of the map's tests that the given test files hold.
 *
 * Pest names each test file's class after its path, so the two are matched as
 * the letters and digits both are spelt with.
 *
 * @param  list<string>  $tests
 * @return array<int, int>
 */
function testsOf(ProcessedCodeCoverageData $data, array $tests): array
{
    $wanted = array_flip(array_map(static fn(string $test): string => testKey(mb_substr($test, 0, -4)), $tests));
    $indexes = [];

    foreach ($data->testIds() as $index => $id) {
        if (array_key_exists(testKey(preg_replace('#^P\\\\#u', '', explode('::', $id, 2)[0]) ?? $id), $wanted)) {
            $indexes[$index] = $index;
        }
    }

    return $indexes;
}

/**
 * Every file at least one of the given tests executed a line of.
 *
 * @param  array<int, int>  $indexes
 * @return list<string>
 */
function filesRunBy(ProcessedCodeCoverageData $data, array $indexes, string $root): array
{
    $reached = [];

    foreach ($data->lineCoverage() as $file => $lines) {
        if (anyLineRunBy($lines, $indexes)) {
            $reached[] = relativeTo($root, $file);
        }
    }

    return $reached;
}

/**
 * Whether any of a file's lines was executed by one of the given tests.
 *
 * @param  array<int, array<int, int>|null>  $lines
 * @param  array<int, int>  $indexes
 */
function anyLineRunBy(array $lines, array $indexes): bool
{
    return array_any($lines, fn(?array $hits): bool => array_intersect_key($hits ?? [], $indexes) !== []);
}

/** A test file's path, or its class, as the letters and digits both are spelt with. */
function testKey(string $name): string
{
    return mb_strtolower(preg_replace('#[^A-Za-z0-9]#u', '', $name) ?? $name);
}
