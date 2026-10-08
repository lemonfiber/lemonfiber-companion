<?php

declare(strict_types=1);

/*
 * Running the gate: one shard, one held path, one floor, and the arguments that choose which.
 *
 * Part of `scripts/mutation.php`, which requires it.
 */

/**
 * One shard's runs: every held path in it against its group, then its files
 * against the suite.
 *
 * @param array{label: string, floor: int, files: list<string>, held: list<array{floor: int, path: string, group: string}>} $shard
 */
function runShard(string $root, array $shard): int
{
    $failed = 0;

    foreach ($shard['held'] as $run) {
        $status = runHeld($root, $run);
        $failed = $failed === 0 ? $status : $failed;
    }

    if ($shard['files'] !== []) {
        fwrite(STDOUT, sprintf("\nMutation at %d%%: %s\n  %s\n", $shard['floor'], $shard['label'], implode("\n  ", $shard['files'])));

        $status = mutate($root, $shard['floor'], $shard['files'], null, []);
        $failed = $failed === 0 ? $status : $failed;
    }

    return $failed === 0 ? 0 : 1;
}

/**
 * A held path, mutated against the group that holds it once the group is shown
 * to cover all of it.
 *
 * @param array{floor: int, path: string, group: string} $run
 */
function runHeld(string $root, array $run): int
{
    fwrite(STDOUT, sprintf("\nMutation at %d%%: %s, judged by %s\n", $run['floor'], $run['path'], $run['group']));

    return theGroupCoversWhatItHolds($root, $run['group'], $run['path']) ? mutate($root, $run['floor'], [$run['path']], $run['group'], []) : 1;
}

/**
 * The value of a `--name=` argument, or null where it was not given.
 *
 * Read off the arguments rather than through `getopt()`, which stops at the
 * first argument it does not recognise and would silently drop everything
 * composer passes through after `--`.
 *
 * `mb_substr` and `mb_strlen` because `L3` forbids the byte versions
 * everywhere, and the rule is right to be blanket: the exception a tree path
 * would earn is the exception somebody copies to a stack name.
 *
 * @param list<string> $given
 */
function argument(array $given, string $prefix): ?string
{
    foreach ($given as $argument) {
        if (str_starts_with($argument, $prefix)) {
            return mb_substr($argument, mb_strlen($prefix));
        }
    }

    return null;
}

/**
 * One mutation run over some trees, at a floor, judged by a group where given.
 *
 * It leaves out the two suites `composer test` does, for the same reasons
 * and with an extra one here. `Floors` reads the clover report rather than
 * producing one, so it fails outright in a run that was never asked for
 * coverage — and a mutation run is exactly that. `Guards` plants violations
 * and runs the analyser and the suite over them as subprocesses, which
 * under mutation would be re-run once per mutant.
 *
 * Nothing was catching this: with no tree holding code, the loops that
 * call this never reached a run at all, so the invocation was unexercised until the
 * first one did.
 *
 * `--covered-only` is what keeps the device-only file out of this. It is
 * excluded from `<source>` in `phpunit.xml`, so no coverage is recorded for
 * it and the runner skips a file it has no covered lines for — which
 * matters more here than it reads: `TheRunloop::start()` blocks against the
 * real bridge, so a mutant of it would hang rather than fail.
 * `--parallel` because this is the one gate whose cost anybody notices, and
 * a gate nobody can afford to re-run is a gate people learn to work around.
 *
 * Parallel is safe because every suite this runs is one `composer test` runs,
 * and that is how `composer test` runs them.
 *
 * Neither the parallelism nor a group changes what is decided: the same
 * mutants are generated and the same floor judges them. A group changes which
 * tests judge them, and is held to covering its tree before it may.
 *
 * @param list<string> $paths
 * @param list<string> $leftOut paths inside these trees that a group judges in a run of their own
 */
function mutate(string $root, int $floor, array $paths, ?string $group, array $leftOut): int
{
    $absolute = static fn(string $path): string => sprintf('%s/%s', $root, $path);

    $command = sprintf(
        '%s%s/vendor/bin/pest --mutate --parallel --covered-only --ignore-min-score-on-zero-mutations --exclude-testsuite=Guards,Floors --min=%d --path=%s%s%s',
        $group === null ? theSharedCoverage() : '',
        escapeshellarg($root),
        $floor,
        escapeshellarg(implode(',', array_map($absolute, $paths))),
        $group === null ? '' : sprintf(' --group=%s', escapeshellarg($group)),
        $leftOut === [] ? '' : sprintf(' --ignore=%s', escapeshellarg(implode(',', array_map($absolute, $leftOut)))),
    );

    passthru($command, $status);

    return $status;
}

/**
 * The coverage map a run against the whole suite takes from the tests job, as
 * the environment the mutation plugin reads, or nothing where none was given.
 *
 * `MUTATION_SHARED_COVERAGE` and `MUTATION_SUITE_SECONDS` name a map
 * `composer test:report` wrote and how long that run took, so a shard opens on
 * the canary rather than on the whole suite a second time: see
 * scripts/patch_pest_mutate_shared_coverage.php. A run a group judges is never
 * given it, because that run's own opening run is the group and nothing else.
 */
function theSharedCoverage(): string
{
    $map = getenv('MUTATION_SHARED_COVERAGE');

    if (! is_string($map) || $map === '') {
        return '';
    }

    return sprintf(
        'LEMONFIBER_MUTATION_COVERAGE=%s LEMONFIBER_MUTATION_SUITE_SECONDS=%s ',
        escapeshellarg($map),
        escapeshellarg((string) getenv('MUTATION_SUITE_SECONDS')),
    );
}
