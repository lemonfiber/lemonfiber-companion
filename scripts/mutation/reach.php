<?php

declare(strict_types=1);

/*
 * What a change reaches, so a run since a revision mutates only what that change can have moved.
 *
 * Part of `scripts/mutation.php`, which requires it.
 */


/**
 * The paths a pull request's change reaches, from what it changed since the
 * commit it is measured against; null where every path has to be mutated.
 *
 * - A changed PHP file under a measured tree is mutated.
 * - A change to a module's own tests, or the bridge's, mutates that module's
 *   whole tree, because those tests are what judge its mutants.
 * - A changed test anywhere mutates every file its tests execute, read from
 *   the coverage map `MUTATION_SHARED_COVERAGE` names; a deleted test, or no
 *   map to read, mutates everything.
 * - A change to what decides how the gate runs — a manifest, `phpunit.xml`,
 *   the Pest bootstrap, `tests/Support`, the application's bootstrap and
 *   config, this script and `.github/workflows/ci.yml` — mutates everything. A
 *   change to that workflow which only moves the revisions its actions are
 *   pinned at is not one: it mutates nothing by itself.
 * - Anything else — documentation, templates, translations, the lock, other
 *   workflows — mutates nothing by itself.
 *
 * A change can still reach a mutant in a file it did not touch: a template
 * that decides what a screen test sees, or a dependency the lock moved. A
 * shard's proof ({@see proofOf()}) covers those files, so a change to them
 * changes the proof.
 *
 * Where git cannot say what changed, every path is mutated.
 *
 * @return list<string>|null
 */
function whatTheChangeReaches(string $root, string $since): ?array
{
    $said = shell_exec(sprintf('git -C %s diff --name-only %s HEAD 2>/dev/null', escapeshellarg($root), escapeshellarg($since)));

    if (! is_string($said)) {
        fwrite(STDERR, sprintf("git could not say what changed since %s, so every path is mutated.\n", $since));

        return null;
    }

    $sorted = sortTheChange($root, $since, array_values(array_filter(explode("\n", $said), static fn(string $line): bool => $line !== '')));
    $tests = $sorted === null ? null : withTheirUsers($root, $sorted['tests']);
    $covered = $tests === null ? null : whatTheTestsReach($root, $tests);

    if ($sorted === null || $covered === null) {
        return null;
    }

    $reach = [...$sorted['reach'], ...$covered];

    fwrite(STDERR, sprintf("The change reaches: %s\n", $reach === [] ? 'no path that is mutated' : implode(', ', $reach)));

    return $reach;
}

/**
 * The paths a change reaches by itself, and the tests it changed, or null
 * where a changed path decides how the gate runs.
 *
 * @param  list<string>  $paths
 * @return array{reach: list<string>, tests: list<string>}|null
 */
function sortTheChange(string $root, string $since, array $paths): ?array
{
    $reach = [];
    $tests = [];

    foreach ($paths as $path) {
        if (decidesHowTheGateRuns($root, $since, $path)) {
            fwrite(STDERR, sprintf("%s decides how the gate runs, so every path is mutated.\n", $path));

            return null;
        }

        $reach = [...$reach, ...whatAPathReaches($path)];

        if (isATest($path)) {
            $tests[] = $path;
        }
    }

    return ['reach' => $reach, 'tests' => $tests];
}

/**
 * What one changed path reaches without the coverage map: a source file
 * itself, and a module's test its module's code.
 *
 * @return list<string>
 */
function whatAPathReaches(string $path): array
{
    if (preg_match('#^(app-modules/[^/]+|bridge)/tests/#u', $path, $found) === 1) {
        return [sprintf('%s/src', $found[1])];
    }

    return str_ends_with($path, '.php') && ! isATest($path) ? [$path] : [];
}

function isATest(string $path): bool
{
    return preg_match('#(^|/)tests/.+\.php$#u', $path) === 1;
}

/**
 * The changed tests, with every changed file of shared test support replaced
 * by the tests that use it, or null for every path.
 *
 * A fake or a helper under `tests/Support` changes what the tests that use it
 * assert, and nothing else: those tests reach the code they execute like any
 * changed test. The support files this script reads to know what is measured
 * are not among these; they decide how the gate runs.
 *
 * @param  list<string>  $tests
 * @return list<string>|null
 */
function withTheirUsers(string $root, array $tests): ?array
{
    $support = array_values(array_filter($tests, static fn(string $test): bool => str_starts_with($test, 'tests/Support/')));
    $users = $support === [] ? [] : testsUsing($root, $support);

    return $users === null ? null : [...array_values(array_diff($tests, $support)), ...$users];
}

/**
 * Every test file that names these support files, directly or through other
 * support that does, or null where something other than a test names one.
 *
 * A name used from the Pest bootstrap, the base test case or a helper file
 * outside `tests/Support` reaches every test, so it mutates every path.
 *
 * @param  list<string>  $support
 * @return list<string>|null
 */
function testsUsing(string $root, array $support): ?array
{
    $pending = array_map(static fn(string $path): string => pathinfo($path, PATHINFO_FILENAME), $support);
    $seen = $pending;
    $users = [];

    while ($pending !== []) {
        $found = usersOf($root, array_pop($pending));

        if ($found['other'] !== []) {
            fwrite(STDERR, sprintf("%s names changed test support and is not a test, so every path is mutated.\n", $found['other'][0]));

            return null;
        }

        $new = array_values(array_diff($found['support'], $seen));
        $seen = [...$seen, ...$new];
        $pending = [...$pending, ...$new];
        $users = [...$users, ...$found['tests']];
    }

    return array_values(array_unique($users));
}

/**
 * The files under the test trees that name a class, sorted into other test
 * support, tests, and anything else.
 *
 * @return array{support: list<string>, tests: list<string>, other: list<string>}
 */
function usersOf(string $root, string $name): array
{
    $said = shell_exec(sprintf('git -C %s grep -l -w -F -e %s -- tests bridge/tests %s', escapeshellarg($root), escapeshellarg($name), escapeshellarg(':(glob)app-modules/*/tests/**')));
    $files = array_values(array_filter(explode("\n", is_string($said) ? $said : ''), static fn(string $file): bool => $file !== ''));
    $support = array_values(array_filter($files, static fn(string $file): bool => str_starts_with($file, 'tests/Support/')));
    $tests = array_values(array_filter($files, static fn(string $file): bool => ! str_starts_with($file, 'tests/Support/') && str_ends_with($file, 'Test.php')));

    return [
        'support' => array_map(static fn(string $file): string => pathinfo($file, PATHINFO_FILENAME), $support),
        'tests' => $tests,
        'other' => array_values(array_diff($files, $support, $tests)),
    ];
}

/**
 * Every file the changed tests execute, read from the coverage map the `tests`
 * job wrote, or null for every path.
 *
 * A test edited to assert less changes no line of the code it judges, so the
 * code it judged is reached through the map rather than through the diff: its
 * mutants are run again, against the test as it now reads. A test the change
 * deleted is not in the map, and neither is a map that was not handed over, so
 * both mutate every path rather than guess at what was judged.
 *
 * @param  list<string>  $tests
 * @return list<string>|null
 */
function whatTheTestsReach(string $root, array $tests): ?array
{
    $map = getenv('MUTATION_SHARED_COVERAGE');
    $gone = array_values(array_filter($tests, static fn(string $test): bool => ! is_file(sprintf('%s/%s', $root, $test))));
    $unanswerable = match (true) {
        ! is_string($map) || ! is_readable($map) => 'No coverage map says what the changed tests execute',
        $gone !== [] => sprintf('%s was deleted', implode(', ', $gone)),
        default => null,
    };

    if ($tests === []) {
        return [];
    }

    if ($unanswerable !== null) {
        fwrite(STDERR, sprintf("%s, so every path is mutated.\n", $unanswerable));

        return null;
    }

    $data = coverageIn($map);

    return filesRunBy($data, testsOf($data, $tests), $root);
}

/**
 * Whether a changed path decides how the gate runs, which mutates every path.
 *
 * `.github/workflows/ci.yml` is one such path, unless all its change did was
 * move the revisions its actions are pinned at.
 */
function decidesHowTheGateRuns(string $root, string $since, string $path): bool
{
    if (preg_match('#^(composer\.json|phpunit\.xml|tests/(Pest|TestCase)\.php|tests/Support/(MeasuredTree|OurCode|Tree|Kind|Module|Imports)\.php|bootstrap/[^/]+|config/.*|scripts/mutation\.php|scripts/mutation/[^/]+\.php|\.github/workflows/ci\.yml|app-modules/[^/]+/composer\.json|bridge/composer\.json)$#u', $path) !== 1) {
        return false;
    }

    if ($path === '.github/workflows/ci.yml' && onlyPinsMoved($root, $since, $path)) {
        fwrite(STDERR, sprintf("%s moved only the revisions its actions are pinned at, so it reaches nothing that is mutated.\n", $path));

        return false;
    }

    return true;
}

/**
 * Whether every line a change touched in a workflow is an action's pin.
 *
 * A pin names the revision a step runs — `uses: owner/repo@<sha> # <tag>` —
 * and moving it changes which revision of that action runs, not how this gate
 * cuts, runs or judges its mutants. Any other line, or a diff git cannot give,
 * is a change to how the gate runs.
 */
function onlyPinsMoved(string $root, string $since, string $path): bool
{
    $said = shell_exec(sprintf('git -C %s diff --unified=0 %s HEAD -- %s 2>/dev/null', escapeshellarg($root), escapeshellarg($since), escapeshellarg($path)));

    if (! is_string($said)) {
        return false;
    }

    $touched = 0;

    foreach (explode("\n", $said) as $line) {
        if (preg_match('#^(\+\+\+|---) #u', $line) === 1 || preg_match('#^[+-]#u', $line) !== 1) {
            continue;
        }

        if (preg_match('#^[+-]\s*(-\s+)?uses:\s*\S+@[0-9a-f]{40}(\s+\#.*)?$#u', $line) !== 1) {
            return false;
        }

        $touched++;
    }

    return $touched > 0;
}
