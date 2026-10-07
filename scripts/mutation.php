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
 * **Three arguments, for spreading the run over several machines.** `--list`
 * prints the shards as a JSON array; `--shard=<id>` runs one of them; and
 * `--shard=<id> --proof` prints what that shard's verdict depends on instead,
 * so a machine can skip a shard whose proof already passed. A
 * shard is a run of files cut to about the same weight — the measured cost of
 * mutating their lines of code — out of every tree at one floor, so one large
 * tree is spread over several runners and several small ones share one. It is
 * the one gate where the work is genuinely separable, because a floor of 100
 * admits no offsetting between files and each is already judged alone.
 *
 * Neither changes what is mutated locally: `composer test:mutation` with no
 * arguments is the whole of it, in one process, which is what somebody running
 * it by hand wants.
 *
 * **A tree, or a path inside one, may name the tests that hold it, and is then
 * judged by those.** A test file that asserts on what some code does, rather
 * than merely passing through it, declares `pest()->group('holds:<path>')`,
 * and that path is mutated against its group instead of against the suite —
 * in a run of its own, with the rest of its tree mutated as before and the
 * held path left out of it. This is for code every test passes through:
 * `bootstrap/Composition` is the composition root, so every test covers it,
 * the covering-test filter for each of its mutants is the whole suite, and a
 * mutant killed by one binding test still costs a full suite run — `--bail`
 * does not stop paratest's other workers. On a runner that made it the one
 * shard taking fifteen to thirty minutes, with most of its mutants recorded as
 * timeouts rather than killed by a test that says what broke. A module's
 * service provider is the same thing one file wide: every test boots it.
 *
 * A group is held to covering all of what it holds before anything is mutated,
 * because `--covered-only` skips a line the group does not reach without
 * saying so — and a line the suite covers and the group does not is a mutant
 * this gate would stop judging in silence.
 */

use Tests\Support\MeasuredTree;

$root = dirname(__DIR__);

// The same derivation the suite uses, reached the only way a script can reach
// it. `--list` therefore needs an install on the machine that asks for it,
// which is the price of the two of them never disagreeing.
require sprintf('%s/vendor/autoload.php', $root);

// What the gate is made of, a file to a concept under `scripts/mutation/`.
require sprintf('%s/mutation/shards.php', __DIR__);
require sprintf('%s/mutation/running.php', __DIR__);
require sprintf('%s/mutation/reach.php', __DIR__);
require sprintf('%s/mutation/coverage.php', __DIR__);
require sprintf('%s/mutation/holding.php', __DIR__);
require sprintf('%s/mutation/proof.php', __DIR__);

/** @var list<string> $given */
$given = array_slice($argv ?? [], 1);

$asked = argument($given, '--shard=');
$listing = in_array('--list', $given, strict: true);
$proving = in_array('--proof', $given, strict: true);
$since = argument($given, '--changed-since=');

$trees = MeasuredTree::all();

if ($trees === []) {
    fwrite(STDERR, "phpunit.xml measures no tree, so there is nothing to mutate.\n");

    exit(1);
}

// Asked for the listing too: a held path is mutated in the shard for held
// paths and left out of every other, so the shards cannot be cut without it.
$holders = whatHoldsEachTree($root, $trees);

/** @var array<int, list<string>> $byFloor */
$byFloor = [];

/** @var list<array{floor: int, path: string, group: string}> $held */
$held = [];

/** @var array<int, list<string>> $leftOut */
$leftOut = [];

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
        if (! $listing && ! $proving) {
            fwrite(STDOUT, sprintf(
                "  %s: mutation floor is 0 — %s\n",
                $tree->path,
                $tree->whyMutationIsZero ?? 'and the manifest says nothing about why',
            ));
        }

        continue;
    }

    // Nothing to mutate yet. Said out loud rather than skipped in silence,
    // because "no mutants" and "every mutant killed" print the same way.
    if ($tree->sourceFiles() === []) {
        if (! $listing && ! $proving) {
            fwrite(STDOUT, sprintf("  %s: no code yet, nothing to mutate\n", $tree->path));
        }

        continue;
    }

    // Its own run, like a tree whose floor differs: a group narrows every
    // tree in an invocation, so one sharing a run would narrow its neighbours.
    // A held path inside a tree is the same, and the tree's own run leaves it
    // out, so no mutant is judged twice or by the wrong tests.
    foreach ($holders as $path => $group) {
        if ($path !== $tree->path && ! str_starts_with($path, sprintf('%s/', $tree->path))) {
            continue;
        }

        $held[] = ['floor' => $floor, 'path' => $path, 'group' => $group];
        $leftOut[$floor][] = $path;
    }

    if (array_key_exists($tree->path, $holders)) {
        continue;
    }

    $byFloor[$floor][] = $tree->path;
}

// The paths a change can reach, or null for every path. Asked of `--list` and
// of `--shard=` alike, so the two cut the same shards.
$reach = $since === null ? null : whatTheChangeReaches($root, $since);

$shards = shardsOf($byFloor, $held, $leftOut, $reach);

// One entry per shard. An empty array is a legitimate answer — no tree holds
// code yet, or the change reaches none of it — and means there is nothing to
// run. Slashes unescaped, because a label names paths and
// `bootstrap\/Composition` is what a shard would be labelled with otherwise.
if ($listing) {
    $matrix = [];

    foreach ($shards as $id => $shard) {
        $matrix[] = ['id' => $id, 'label' => $shard['label']];
    }

    fwrite(STDOUT, sprintf("%s\n", json_encode($matrix, JSON_UNESCAPED_SLASHES)));

    exit(0);
}

if ($asked !== null) {
    if (! array_key_exists($asked, $shards)) {
        fwrite(STDERR, sprintf(<<<'SAID'
            There is no shard %s here; this commit cuts %d.

            A shard naming one that is gone is a shard that passes having done nothing,
            which is the whole failure this gate exists to prevent. The matrix is built
            from `--list` on the same commit, so this means the two disagree.

            SAID, $asked, count($shards)));

        exit(1);
    }

    // What the shard's verdict depends on, instead of the verdict: a shard whose
    // proof already passed on this branch or on `main` is not run again.
    if ($proving) {
        fwrite(STDOUT, sprintf("%s\n", proofOf($root, $shards[$asked])));

        exit(0);
    }

    exit(runShard($root, $shards[$asked]));
}

if ($byFloor === [] && $held === []) {
    fwrite(STDOUT, "No measured tree has code to mutate yet.\n");

    exit(0);
}

$failed = 0;

foreach ($held as $run) {
    $status = runHeld($root, $run);
    $failed = $failed === 0 ? $status : $failed;
}

foreach ($byFloor as $floor => $paths) {
    fwrite(STDOUT, sprintf("\nMutation at %d%%: %s\n", $floor, implode(', ', $paths)));

    // Most floors hold no path a group judges apart, and those have nothing
    // to leave out: absent here is an answer, not a gap.
    $status = mutate($root, $floor, $paths, null, array_key_exists($floor, $leftOut) ? $leftOut[$floor] : []);
    $failed = $failed === 0 ? $status : $failed;
}

exit($failed === 0 ? 0 : 1);
