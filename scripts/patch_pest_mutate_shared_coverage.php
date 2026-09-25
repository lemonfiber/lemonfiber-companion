<?php

declare(strict_types=1);

/*
 * A mutation shard can take its coverage map from the run of the suite that
 * already made one, instead of running the whole suite again to make its own.
 *
 * Pest's mutation plugin opens every run with the full suite under coverage:
 * that run is how it learns which tests execute which line, and so which tests
 * each mutant is run against. In CI the `tests` job has already run exactly
 * that suite, under coverage, on the same commit, and every mutation shard ran
 * it again — about five minutes a shard, twenty times over, all identical.
 *
 * With `LEMONFIBER_MUTATION_COVERAGE` naming a map written by that run, and
 * `LEMONFIBER_MUTATION_SUITE_SECONDS` saying how long the run took:
 *
 * - The opening run is only the tests in the `mutation-canary` group, which
 *   boot the application. A shard whose application cannot boot still fails
 *   there, as it would have failed the full run.
 * - The plugin reads the shared map in place of the canary's, and times each
 *   mutant out against the recorded duration, as it would have against its own.
 * - Every mutant still runs with the arguments the shard was given, narrowed
 *   to the tests the map says cover its line. The canary group is added to the
 *   opening run only, after those arguments are kept for the mutants.
 *
 * A map that cannot be read, or a duration that is missing or not above zero,
 * stops the shard rather than letting it run: every mutant would time out at
 * the plugin's five-second floor, and a timeout is not a test saying what broke.
 *
 * Without the variables the plugin runs as it ships. `scripts/mutation.php`
 * sets them only for the runs it mutates against the whole suite, never for a
 * path a group holds, whose opening run is that group and nothing else.
 *
 * Every anchor below is checked before anything is written. An anchor that has
 * moved stops the install, because a patch that quietly matched nothing would
 * leave shards reading the canary's map for the whole suite's: every line the
 * canary does not reach would be skipped as uncovered, and `--covered-only`
 * says nothing when it skips.
 */

/** Each file this rewrites, relative to this script, with what it ships and what it becomes. */
const PATCHES = [
    '/../vendor/pestphp/pest-plugin-mutate/src/Plugins/Mutate.php' => [
        <<<'SHIPS'
                    $mutationTestRunner->setOriginalArguments($arguments);
                    $mutationTestRunner->setStartTime(microtime(true));

                    return $arguments;
            SHIPS,
        <<<'BECOMES'
                    $mutationTestRunner->setOriginalArguments($arguments);
                    $mutationTestRunner->setStartTime(microtime(true));

                    // A shared coverage map replaces this run's own, so only the canary
                    // runs here; every mutant keeps the arguments above. See
                    // scripts/patch_pest_mutate_shared_coverage.php.
                    if (is_string(getenv('LEMONFIBER_MUTATION_COVERAGE')) && getenv('LEMONFIBER_MUTATION_COVERAGE') !== '') {
                        $arguments[] = '--group=mutation-canary';
                    }

                    return $arguments;
            BECOMES,
    ],
    '/../vendor/pestphp/pest-plugin-mutate/src/Tester/MutationTestRunner.php' => [
        <<<'SHIPS'
                    Container::getInstance()->get(TelemetryRepository::class)->initialTestSuiteDuration( // @phpstan-ignore-line
                        microtime(true) - $this->startTime
                    );
            SHIPS,
        <<<'BECOMES'
                    Container::getInstance()->get(TelemetryRepository::class)->initialTestSuiteDuration( // @phpstan-ignore-line
                        microtime(true) - $this->startTime
                    );

                    // A shared coverage map, and the duration of the run that wrote it. See
                    // scripts/patch_pest_mutate_shared_coverage.php.
                    $shared = getenv('LEMONFIBER_MUTATION_COVERAGE');

                    if (is_string($shared) && $shared !== '') {
                        $seconds = (float) getenv('LEMONFIBER_MUTATION_SUITE_SECONDS');

                        if (! is_readable($shared) || $seconds <= 0.0 || ! copy($shared, Coverage::getPath())) {
                            Container::getInstance()->get(Printer::class)->reportError(sprintf('The shared coverage map %s, or the duration of the run that wrote it, could not be read; aborting mutation testing.', $shared)); // @phpstan-ignore-line

                            return 1;
                        }

                        Container::getInstance()->get(TelemetryRepository::class)->initialTestSuiteDuration($seconds); // @phpstan-ignore-line
                    }
            BECOMES,
    ],
];

$sources = [];

foreach (PATCHES as $where => [$ships, $becomes]) {
    $path = sprintf('%s%s', __DIR__, $where);
    $source = file_exists($path) ? file_get_contents($path) : false;

    if (! is_string($source)) {
        fwrite(STDERR, sprintf("patch_pest_mutate_shared_coverage: %s is not there or cannot be read.\n\nThe mutation plugin no longer ships a file this patch rewrites. Point this at the new path, or delete this script, its `composer.json` hooks and the `MUTATION_SHARED_COVERAGE` step in CI. Do not ignore this.\n", $path));

        exit(1);
    }

    if (! str_contains($source, $becomes) && ! str_contains($source, $ships)) {
        fwrite(STDERR, sprintf("patch_pest_mutate_shared_coverage: the lines this patch rewrites are not in %s.\n\nThe plugin changed. Until this is pointed at what it ships now, a shard handed a shared map reads the canary's coverage for the whole suite's and skips every line the canary does not reach. Do not ignore this.\n", $path));

        exit(1);
    }

    $sources[$path] = [$source, $ships, $becomes];
}

$rewritten = 0;

foreach ($sources as $path => [$source, $ships, $becomes]) {
    if (str_contains($source, $becomes)) {
        continue;
    }

    file_put_contents($path, str_replace($ships, $becomes, $source));
    $rewritten++;
}

fwrite(STDOUT, $rewritten === 0
    ? "patch_pest_mutate_shared_coverage: already applied.\n"
    : "patch_pest_mutate_shared_coverage: a shard can take its coverage map from the tests job.\n");
