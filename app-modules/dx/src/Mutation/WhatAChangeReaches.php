<?php

declare(strict_types=1);

namespace Dx\Mutation;

use function array_diff;
use function array_filter;
use function array_map;
use function array_pop;
use function array_unique;
use function array_values;
use function implode;
use function pathinfo;
use function sprintf;
use function str_ends_with;

/**
 * The paths a change reaches, from what it changed since the commit it is
 * measured against.
 *
 * - A changed PHP file that is not a test is mutated.
 * - A changed test mutates the trees it judges ({@see TheLayout::treesATestJudges()}),
 *   and every file its tests execute, read from the coverage map the tests job
 *   wrote; a deleted test, or no map to read, mutates everything.
 * - A changed file of shared test support reaches the tests that name it,
 *   through any support that names it in turn; a name used anywhere else under
 *   the test directories mutates everything.
 * - A change to what decides how the gate runs mutates everything, unless all
 *   it did was move the revisions a workflow's actions are pinned at.
 * - Anything else mutates nothing by itself.
 *
 * A change can still reach a mutant in a file it did not touch: a template that
 * decides what a test sees, or a dependency the lock moved. A unit's proof
 * covers those files, so a unit whose proof they moved is mutated again rather
 * than skipped wherever it is reached.
 */
final readonly class WhatAChangeReaches
{
    public function __construct(private TheRepository $repository, private TheLayout $layout, private ?TheCoverageMap $map) {}

    public function since(string $since): AReach
    {
        $changed = $this->repository->changedSince($since);

        if ($changed === null) {
            return AReach::everything(sprintf('git could not say what changed since %s, so every path is mutated.', $since));
        }

        $reach = new AReach([], []);

        foreach ($changed as $path) {
            $reach = $this->withPath($reach, $since, $path);
        }

        return $reach->isEverything()
            ? $reach
            : $this->withTests($reach, array_values(array_filter($changed, $this->layout->isATest(...))));
    }

    private function withPath(AReach $reach, string $since, string $path): AReach
    {
        if ($reach->isEverything()) {
            return $reach;
        }

        if (! $this->layout->decidesHowTheGateRuns($path)) {
            return new AReach([...$reach->paths ?? [], ...$this->whatAPathReaches($path)], $reach->said);
        }

        if ($this->layout->onlyPinsMoved($path, $this->repository->changeTo($since, $path) ?? '')) {
            return new AReach($reach->paths, [...$reach->said, sprintf('%s moved only the revisions its actions are pinned at, so it reaches nothing that is mutated.', $path)]);
        }

        return new AReach(null, [...$reach->said, sprintf('%s decides how the gate runs, so every path is mutated.', $path)]);
    }

    /** @param list<string> $tests */
    private function withTests(AReach $reach, array $tests): AReach
    {
        $users = $this->withTheirUsers($tests);
        $covered = $users->isEverything() ? $users : $this->whatTheTestsReach($users->paths ?? []);

        if ($covered->isEverything()) {
            return new AReach(null, [...$reach->said, ...$covered->said]);
        }

        $paths = [...$reach->paths ?? [], ...$covered->paths ?? []];

        return new AReach($paths, [...$reach->said, sprintf('The change reaches: %s', $paths === [] ? 'no path that is mutated' : implode(', ', $paths))]);
    }

    /**
     * What one changed path reaches without the coverage map: a PHP file that
     * is not a test, itself, and a test the trees it judges.
     *
     * @return list<string>
     */
    private function whatAPathReaches(string $path): array
    {
        return str_ends_with($path, '.php') && ! $this->layout->isATest($path) ? [$path] : $this->layout->treesATestJudges($path);
    }

    /**
     * The changed tests, with every changed file of shared support replaced by
     * the tests that use it: a fake or a helper changes what the tests that use
     * it assert, and those tests reach the code they execute like any changed
     * test.
     *
     * @param list<string> $tests
     */
    private function withTheirUsers(array $tests): AReach
    {
        $support = array_values(array_filter($tests, $this->layout->isSharedTestSupport(...)));

        if ($support === []) {
            return new AReach($tests, []);
        }

        $users = $this->testsUsing($support);

        return $users->isEverything() ? $users : new AReach([...array_values(array_diff($tests, $support)), ...$users->paths ?? []], []);
    }

    /**
     * Every test file that names these support files, directly or through
     * other support that does. A name used anywhere else under the test
     * directories — the Pest bootstrap, the base test case, a dataset — reaches
     * every test.
     *
     * @param list<string> $support
     */
    private function testsUsing(array $support): AReach
    {
        $pending = array_map(static fn(string $path): string => pathinfo($path, PATHINFO_FILENAME), $support);
        $seen = $pending;
        $users = [];

        while ($pending !== []) {
            $found = $this->usersOf(array_pop($pending));

            if ($found['other'] !== []) {
                return AReach::everything(sprintf('%s names changed test support and is not a test, so every path is mutated.', $found['other'][0]));
            }

            $new = array_values(array_diff($found['support'], $seen));
            $seen = [...$seen, ...$new];
            $pending = [...$pending, ...$new];
            $users = [...$users, ...$found['tests']];
        }

        return new AReach(array_values(array_unique($users)), []);
    }

    /**
     * The files under the test directories that name a class, sorted into
     * shared support, test cases, and anything else.
     *
     * @return array{support: list<string>, tests: list<string>, other: list<string>}
     */
    private function usersOf(string $name): array
    {
        $files = $this->repository->testFilesNaming($name);
        $support = array_values(array_filter($files, $this->layout->isSharedTestSupport(...)));
        $tests = array_values(array_filter($files, fn(string $file): bool => ! $this->layout->isSharedTestSupport($file) && $this->layout->isATestCase($file)));

        return [
            'support' => array_map(static fn(string $file): string => pathinfo($file, PATHINFO_FILENAME), $support),
            'tests' => $tests,
            'other' => array_values(array_diff($files, $support, $tests)),
        ];
    }

    /**
     * Every file the changed tests execute, read from the coverage map.
     *
     * A test edited to assert less changes no line of the code it judges, so
     * that code is reached through the map rather than through the diff: its
     * mutants are run again, against the test as it now reads. A test the
     * change deleted is not in the map, and neither is a map that was not
     * handed over, so both mutate every path rather than guess.
     *
     * @param list<string> $tests
     */
    private function whatTheTestsReach(array $tests): AReach
    {
        if ($tests === []) {
            return new AReach([], []);
        }

        $gone = array_values(array_filter($tests, fn(string $test): bool => ! $this->repository->exists($test)));

        if (! $this->map instanceof TheCoverageMap || $gone !== []) {
            return AReach::everything(sprintf('%s, so every path is mutated.', $this->map instanceof TheCoverageMap ? sprintf('%s was deleted', implode(', ', $gone)) : 'No coverage map says what the changed tests execute'));
        }

        return new AReach($this->map->filesRunBy($tests), []);
    }
}
