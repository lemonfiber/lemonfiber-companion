<?php

declare(strict_types=1);

/*
 * Mutation testing, at the floor declared for each tree the suite measures.
 *
 * Coverage floors are read out of the clover report by the `Floors` suite.
 * Mutation floors cannot be: there is no machine-readable mutation report —
 * Pest offers `--min`, which fails a run, and nothing that emits a score. So
 * the floor is enforced by invocation rather than by reading, and this is what
 * does the invoking.
 *
 * Trees that share a floor share a run. A floor of 100 admits no offsetting
 * between them — one surviving mutant anywhere in the path list drops the score
 * below 100 and fails — so grouping costs nothing in rigour and saves a full
 * suite pass per tree, which is what each extra invocation actually costs.
 * A tree whose floor differs gets its own run, because that is exactly where
 * a shared one would let the stricter tree carry the looser.
 *
 * **The trees and their floors come from `Tests\Support\MeasuredTree`, which is
 * where the `Floors` suite and `G7` get them too.** They are the trees
 * `phpunit.xml` measures, and each is held to what the manifest nearest it
 * declares. A glob written here instead is a second answer to *what is
 * measured*, and the second answer was wrong: `app-modules/*` reached thirteen
 * modules and neither `bridge/src` nor `bootstrap/Composition`, which are 2,900
 * lines of shipped PHP the coverage floor holds and this file walked straight
 * past — silently, because the run still passed, over less.
 *
 * **Two arguments, both for CI.** `--list` prints the trees worth mutating as
 * a JSON array, which is what a workflow matrix reads; `--tree=<path>` narrows
 * a run to one of them. Together they let the slowest gate in the repository
 * run a tree per runner instead of all of them in a row — it is the one gate
 * where the work is genuinely separable, because a floor of 100 admits no
 * offsetting between trees and each is already judged alone.
 *
 * Neither changes what is mutated locally: `composer test:mutation` with no
 * arguments is the whole of it, in one process, which is what somebody running
 * it by hand wants.
 */

use Tests\Support\MeasuredTree;

$root = dirname(__DIR__);

// The same derivation the suite uses, reached the only way a script can reach
// it. `--list` therefore needs an install on the runner that asks for it,
// which is the price of the two of them never disagreeing.
require sprintf('%s/vendor/autoload.php', $root);

/** @var list<string> $given */
$given = array_slice($argv ?? [], 1);

$asked = argument($given, '--tree=');
$listing = in_array('--list', $given, strict: true);

$trees = MeasuredTree::all();

if ($trees === []) {
    fwrite(STDERR, "phpunit.xml measures no tree, so there is nothing to mutate.\n");

    exit(1);
}

/** @var array<int, list<string>> $byFloor */
$byFloor = [];

/** @var list<string> $worthMutating */
$worthMutating = [];

foreach ($trees as $tree) {
    $floor = $tree->mutationFloor;

    if ($floor === null) {
        // A nowdoc rather than lines joined with `.`, which is `H5`: every join
        // between two literals is three mutants nothing can kill, and paragraphs
        // this shape cannot honestly be one line. It also takes the escaping
        // away — the braces below are what a manifest actually looks like.
        fwrite(STDERR, sprintf(<<<'SAID'
            %s is measured and %s declares no mutation floor.

            Add it to the manifest nearest that tree, beside the kind where there is one:
                "extra": { "lemonfiber": { "floors": { "mutation": 100 } } }

            There is no default on purpose — a tree that inherits one is exempt from
            the decision rather than held to it. G7 reports the same omission in the
            test suite, so this should already have failed there.

            SAID, $tree->path, $tree->manifest));

        exit(1);
    }

    // A floor of zero is a declared position rather than a gap, and the
    // position is that manifest's own: it says what holds those decisions
    // instead, beside the number. Said out loud rather than skipped in silence,
    // because a tree that was never mutated and a tree with nothing left to
    // kill print the same way — which is nothing at all.
    if ($floor === 0) {
        if (! $listing) {
            fwrite(STDOUT, sprintf(
                "  %s: mutation floor is 0 — %s\n",
                $tree->path,
                $tree->whyMutationIsZero ?? 'and the manifest says nothing about why',
            ));
        }

        continue;
    }

    // Narrowed to one tree where a runner was given one. The floor is still
    // read for every tree rather than only this one, because the refusal
    // above is the check that a tree was declared a floor at all — and a shard
    // that skipped it would let an undeclared floor through on fourteen runners
    // out of fifteen.
    if ($asked !== null && $asked !== $tree->path) {
        continue;
    }

    // Nothing to mutate yet. Said out loud rather than skipped in silence,
    // because "no mutants" and "every mutant killed" print the same way.
    if ($tree->sourceFiles() === []) {
        if (! $listing) {
            fwrite(STDOUT, sprintf("  %s: no code yet, nothing to mutate\n", $tree->path));
        }

        continue;
    }

    if ($listing) {
        $worthMutating[] = $tree->path;

        continue;
    }

    $byFloor[$floor][] = $tree->path;
}

// What a workflow matrix reads. An empty array is a legitimate answer — no
// tree holds code yet — and a matrix over it runs nothing, which is why the
// job that aggregates the shards has to treat "nothing ran" as a pass rather
// than as an absence.
if ($listing) {
    // Not sorted here, and that is not an omission. The trees arrive in the
    // order `phpunit.xml` writes them, with each glob expanded by `glob()`,
    // which sorts — so this list is stable across commits, which is all a
    // matrix needs so that its runners do not reshuffle. Sorting it again would
    // have meant reaching for a byte comparison `L6` forbids, and claiming an
    // exemption for a line that changes nothing is worse than the line.
    // Slashes unescaped, because a tree is a path now rather than a bare name
    // and `bootstrap\/Composition` is what a runner would be labelled with.
    fwrite(STDOUT, sprintf("%s\n", json_encode($worthMutating, JSON_UNESCAPED_SLASHES)));

    exit(0);
}

if ($asked !== null && $byFloor === []) {
    fwrite(STDERR, sprintf(<<<'SAID'
        There is no measured tree at %s with code to mutate.

        A shard naming one that is gone is a shard that passes having done nothing,
        which is the whole failure this gate exists to prevent. The matrix is built
        from `--list` on the same commit, so this means the two disagree.

        SAID, $asked));

    exit(1);
}

if ($byFloor === []) {
    fwrite(STDOUT, "No measured tree has code to mutate yet.\n");

    exit(0);
}

$failed = 0;

foreach ($byFloor as $floor => $paths) {
    fwrite(STDOUT, sprintf("\nMutation at %d%%: %s\n", $floor, implode(', ', $paths)));

    // The same two suites `composer test` leaves out, for the same reasons and
    // with an extra one here. `Floors` reads the clover report rather than
    // producing one, so it fails outright in a run that was never asked for
    // coverage — and a mutation run is exactly that. `Guards` plants violations
    // and runs the analyser and the suite over them as subprocesses, which
    // under mutation would be re-run once per mutant.
    //
    // Nothing was catching this: with no tree holding code, the loop above
    // never reached a run at all, so the invocation was unexercised until the
    // first one did.
    //
    // `--covered-only` is what keeps the device-only file out of this. It is
    // excluded from `<source>` in `phpunit.xml`, so no coverage is recorded for
    // it and the runner skips a file it has no covered lines for — which
    // matters more here than it reads: `TheRunloop::start()` blocks against the
    // real bridge, so a mutant of it would hang rather than fail.
    // `--parallel` because this is the one gate whose cost anybody notices.
    // `bootstrap/Composition` takes seventy-six to ninety-one minutes on a
    // runner where every other tree takes three to five: it is the composition
    // root, so almost every test touches it, and a floor of 100 means each
    // mutant is judged by a suite run. A gate nobody can afford to re-run is a
    // gate people learn to work around.
    //
    // Safe for the same reason `composer test` is: parallelism here is
    // paratest's, and the two suites that cannot survive it are already
    // excluded below — `Guards` plants violations into the working tree that a
    // neighbouring process would see appear and vanish, and `Floors` reads a
    // clover report this run never asks for. Those exclusions are what make the
    // ordinary suite parallel, and they are the same ones.
    //
    // It changes what the run costs and not what it decides: the same mutants
    // are generated and the same floor judges them.
    $command = sprintf(
        '%s/vendor/bin/pest --mutate --parallel --covered-only --ignore-min-score-on-zero-mutations --exclude-testsuite=Guards,Floors --min=%d --path=%s',
        escapeshellarg($root),
        $floor,
        escapeshellarg(implode(',', array_map(
            static fn(string $path): string => sprintf('%s/%s', $root, $path),
            $paths,
        ))),
    );

    passthru($command, $status);

    $failed = $failed === 0 ? $status : $failed;
}

exit($failed === 0 ? 0 : 1);

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
