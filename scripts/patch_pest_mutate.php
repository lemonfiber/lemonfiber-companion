<?php

declare(strict_types=1);

/*
 * A filter that names the whole suite is an argument the kernel will not take.
 *
 * Pest's mutation plugin runs each mutant in a child process, narrowed to the
 * tests that cover the mutated line. It builds that narrowing as a single
 * argument — one `--filter=` holding a regex that alternates every covering
 * test — and that argument has no ceiling of its own. On the CI runners it
 * grows past one the kernel enforces.
 *
 * **Which ceiling is not established, and this script does not claim to know.**
 * The obvious candidate is `MAX_ARG_STRLEN`, thirty-two pages — 131072 bytes
 * where a page is 4 KiB. That was not reproducible off the runners: a Linux
 * container with a 4 KiB page size spawned a four-megabyte single argument
 * without complaint, so either that kernel does not enforce it or the limit
 * being hit in CI is a different one. What is established is the failure, the
 * mechanism that produces an unbounded argument, and that it began when the
 * suite grew rather than when any of that code changed. The threshold below is
 * therefore chosen to sit under every candidate rather than derived from the
 * one that bites, and CI is what shows whether it is low enough.
 *
 * A line executed by every test that boots the application therefore cannot be
 * mutated at all. The child is never spawned and the run dies with
 *
 *     proc_open(): posix_spawn() failed: Argument list too long
 *
 * which is not a surviving mutant and not a score. It is the gate failing to
 * start, and it reads in CI exactly like the gate failing to pass.
 *
 * Two lines in this application are already past it — the route registrations
 * in `HouseholdServiceProvider` and `OperatorServiceProvider`, both inside
 * `$this->app->booted()`, both reached by every test that boots. The eighteen
 * mutants there are all killed today, and two of them are what say a screen is
 * reachable at all. It gets worse with every test added, including the tests
 * added to kill mutants, which is the part worth noticing: the gate degrades
 * as the suite it measures gets stronger.
 *
 * **So the filter is dropped when it will not fit, and the child runs the whole
 * suite for that mutant.** The covering tests are a subset of the suite, so an
 * unfiltered run executes everything the filtered one would have and more: it
 * can kill a mutant the filter would have killed, and it can kill one the
 * filter would have missed. It cannot let one survive. The change moves the
 * gate in one direction only, and that direction is stricter.
 *
 * It costs almost nothing, for the reason it is needed at all. It fires only on
 * mutants whose covering set is already nearly the whole suite, and the child
 * runs with `--bail` — so a mutant that is killed stops the run at the test
 * that kills it, filtered or not.
 *
 * The alternative was excluding those two files from mutation, which would have
 * deleted eighteen passing checks in a file whose own comment records the
 * mutation run catching a dead line in it.
 *
 * Run from `post-install-cmd` and `post-update-cmd`, so it survives the next
 * `composer install` rather than being a thing somebody remembers. It refuses
 * to be a no-op in every direction it can: a package that is not there, a file
 * it cannot read, and an anchor that has moved all stop the install, because a
 * patch that quietly matched nothing would leave the gate dying on a limit with
 * this file in the tree looking applied.
 */

/** The file this rewrites, relative to this script. */
const WHERE = '/../vendor/pestphp/pest-plugin-mutate/src/MutationTest.php';

/**
 * The ceiling this holds the argument to.
 *
 * Conservative rather than derived, for the reason the header gives: the limit
 * actually being enforced in CI is not established, so this sits below the
 * lowest plausible one instead of just under a number that might be the wrong
 * number. It is also not the only argument in the list, and some candidates
 * bound the whole command rather than one argument.
 *
 * Being wrong in the low direction costs time on a handful of mutants and
 * nothing else — they run unfiltered and are judged correctly. Being wrong in
 * the high direction leaves the gate dying exactly as it does now, which CI
 * will say. There is no outcome where a lower number is unsafe, which is why
 * this one is not tuned.
 */
const CEILING = 100_000;

const SHIPS = <<<'SHIPS'
        $process = new Process(
            command: [
                ...$filteredArguments,
                '--bail',
                '--filter="'.implode('|', $filters).'"',
            ],
SHIPS;

const BECOMES = <<<'BECOMES'
        $filter = '--filter="'.implode('|', $filters).'"';

        $process = new Process(
            command: [
                ...$filteredArguments,
                '--bail',
                // Dropped when it will not fit. The covering tests are a subset
                // of the suite, so running unfiltered kills at least what the
                // filter would have — never fewer. See scripts/patch_pest_mutate.php.
                ...(strlen($filter) < 100000 ? [$filter] : []),
            ],
BECOMES;

const WHEN_THE_PACKAGE_IS_NOT_THERE = "patch_pest_mutate: %s is not there.\n\nThe mutation plugin no longer ships the file this patch rewrites. Either it was renamed, in which case point this at the new path, or the package is gone, in which case delete this script and the two `composer.json` hooks that call it. Skipping it quietly leaves the mutation gate dying on an argument-length limit with this patch in the tree looking applied. Do not ignore this.\n";

const WHEN_THE_LINES_HAVE_MOVED = "patch_pest_mutate: the lines this patch rewrites are not in %s.\n\nEither the package fixed it — in which case delete this script and the two `composer.json` hooks that call it — or it moved, in which case the mutation gate is about to start dying with `posix_spawn() failed: Argument list too long` on any line the whole suite covers, and nothing will have said so. Do not ignore this.\n";

$path = sprintf('%s%s', __DIR__, WHERE);

if (! file_exists($path)) {
    fwrite(STDERR, sprintf(WHEN_THE_PACKAGE_IS_NOT_THERE, $path));

    exit(1);
}

$source = file_get_contents($path);

if (! is_string($source)) {
    fwrite(STDERR, sprintf("patch_pest_mutate: could not read %s.\n", $path));

    exit(1);
}

// Already applied. Said rather than counted as a rewrite, because `composer
// install` runs this twice in a row often enough that a second pass finding
// nothing is the ordinary case rather than the suspicious one.
if (str_contains($source, BECOMES)) {
    fwrite(STDOUT, "patch_pest_mutate: already applied.\n");

    exit(0);
}

if (! str_contains($source, SHIPS)) {
    fwrite(STDERR, sprintf(WHEN_THE_LINES_HAVE_MOVED, $path));

    exit(1);
}

file_put_contents($path, str_replace(SHIPS, BECOMES, $source));

fwrite(STDOUT, sprintf("patch_pest_mutate: a filter over %d bytes now runs unfiltered.\n", CEILING));
