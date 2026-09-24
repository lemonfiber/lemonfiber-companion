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
 *
 * **A tree may name the tests that hold it, and is then judged by those.** A
 * test file that runs a tree rather than merely passing through it declares
 * `pest()->group('holds:<tree>')`, and that tree is mutated against its group
 * instead of against the suite. This is for a tree every test passes through:
 * `bootstrap/Composition` is the composition root, so every test covers it,
 * the covering-test filter for each of its mutants is the whole suite, and a
 * mutant killed by one binding test still costs a full suite run — `--bail`
 * does not stop paratest's other workers. On a runner that made it the one
 * shard taking fifteen to thirty minutes, with most of its mutants recorded as
 * timeouts rather than killed by a test that says what broke.
 *
 * A group is held to covering its whole tree before anything is mutated,
 * because `--covered-only` skips a line the group does not reach without
 * saying so — and a line the suite covers and the group does not is a mutant
 * this gate would stop judging in silence.
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

$holders = $listing ? [] : whatHoldsEachTree($root, $trees);

/** @var array<int, list<string>> $byFloor */
$byFloor = [];

/** @var list<array{floor: int, tree: string, group: string}> $held */
$held = [];

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

    // Its own run, like a tree whose floor differs: a group narrows every
    // tree in an invocation, so one sharing a run would narrow its neighbours.
    if (array_key_exists($tree->path, $holders)) {
        $held[] = ['floor' => $floor, 'tree' => $tree->path, 'group' => $holders[$tree->path]];

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

if ($asked !== null && $byFloor === [] && $held === []) {
    fwrite(STDERR, sprintf(<<<'SAID'
        There is no measured tree at %s with code to mutate.

        A shard naming one that is gone is a shard that passes having done nothing,
        which is the whole failure this gate exists to prevent. The matrix is built
        from `--list` on the same commit, so this means the two disagree.

        SAID, $asked));

    exit(1);
}

if ($byFloor === [] && $held === []) {
    fwrite(STDOUT, "No measured tree has code to mutate yet.\n");

    exit(0);
}

$failed = 0;

foreach ($held as $run) {
    fwrite(STDOUT, sprintf("\nMutation at %d%%: %s, judged by %s\n", $run['floor'], $run['tree'], $run['group']));

    $status = theGroupCoversTheTree($root, $run['group'], $run['tree']) ? mutate($root, $run['floor'], [$run['tree']], $run['group']) : 1;
    $failed = $failed === 0 ? $status : $failed;
}

foreach ($byFloor as $floor => $paths) {
    fwrite(STDOUT, sprintf("\nMutation at %d%%: %s\n", $floor, implode(', ', $paths)));

    $status = mutate($root, $floor, $paths, null);
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
 * Safe for the same reason `composer test` is: parallelism here is
 * paratest's, and the two suites that cannot survive it are already
 * excluded here — `Guards` plants violations into the working tree that a
 * neighbouring process would see appear and vanish, and `Floors` reads a
 * clover report this run never asks for. Those exclusions are what make the
 * ordinary suite parallel, and they are the same ones.
 *
 * Neither the parallelism nor a group changes what is decided: the same
 * mutants are generated and the same floor judges them. A group changes which
 * tests judge them, and is held to covering its tree before it may.
 *
 * @param list<string> $paths
 */
function mutate(string $root, int $floor, array $paths, ?string $group): int
{
    $command = sprintf(
        '%s/vendor/bin/pest --mutate --parallel --covered-only --ignore-min-score-on-zero-mutations --exclude-testsuite=Guards,Floors --min=%d --path=%s%s',
        escapeshellarg($root),
        $floor,
        escapeshellarg(implode(',', array_map(
            static fn(string $path): string => sprintf('%s/%s', $root, $path),
            $paths,
        ))),
        $group === null ? '' : sprintf(' --group=%s', escapeshellarg($group)),
    );

    passthru($command, $status);

    return $status;
}

/**
 * Each tree a group declares it holds, by the tree's path.
 *
 * Asked of the suite rather than read out of test sources, so the answer is
 * the groups Pest will actually select by. A group naming a tree nothing
 * measures is refused rather than ignored: a misspelt tree would otherwise be
 * mutated against the whole suite again, correct and slow and with no sign the
 * declaration was never read.
 *
 * @param  list<MeasuredTree>    $trees
 * @return array<string, string>
 */
function whatHoldsEachTree(string $root, array $trees): array
{
    $said = shell_exec(sprintf('%s/vendor/bin/pest --list-groups --colors=never 2>&1', escapeshellarg($root)));
    $said = is_string($said) ? $said : '';

    // Refused rather than read as *no groups*: a listing that failed and one
    // that found none would otherwise both mutate every tree against the whole
    // suite, and only one of them is true.
    if (! str_contains($said, 'Available test group')) {
        fwrite(STDERR, sprintf("Pest could not list the suite's groups:\n%s\n", $said));

        exit(1);
    }

    $listed = explode("\n", $said);

    $measured = array_map(static fn(MeasuredTree $tree): string => $tree->path, $trees);
    $holders = [];

    foreach ($listed as $line) {
        if (preg_match('/^\s*-\s+(holds:(\S+))\s+\(/u', $line, $found) !== 1) {
            continue;
        }

        if (! in_array($found[2], $measured, strict: true)) {
            fwrite(STDERR, sprintf(<<<'SAID'
                A test declares the group %s, and %s is not a tree phpunit.xml measures.

                The group names the tree its tests hold, so the name has to be one of
                the measured trees exactly — otherwise the tree it meant is mutated
                against the whole suite again, and nothing says why it is slow.

                SAID, $found[1], $found[2]));

            exit(1);
        }

        $holders[$found[2]] = $found[1];
    }

    return $holders;
}

/**
 * Whether a group reaches every line of its tree that anything could reach.
 *
 * Measured the way `test:report` measures, over the group alone, and held to
 * every statement in the tree: the coverage floor already holds the suite to
 * all of them, so a statement the group misses is one the suite reaches and
 * the group does not — and `--covered-only` would drop its mutants without a
 * word. Files `phpunit.xml` leaves out of `<source>` are not in the report,
 * which is the same exemption the device-only runloop has everywhere else.
 */
function theGroupCoversTheTree(string $root, string $group, string $tree): bool
{
    $clover = sprintf('%s/lemonfiber-held-%s.xml', sys_get_temp_dir(), hash('sha256', $group));

    passthru(sprintf(
        "php -d pcov.directory=%s -d pcov.exclude='~/(vendor|bootstrap/cache)/~' %s/vendor/bin/pest --group=%s --coverage-clover=%s",
        escapeshellarg($root),
        escapeshellarg($root),
        escapeshellarg($group),
        escapeshellarg($clover),
    ), $status);

    if ($status !== 0 || ! is_file($clover)) {
        fwrite(STDERR, sprintf("The group %s did not pass on its own, so it cannot judge %s.\n", $group, $tree));

        return false;
    }

    $missed = whatTheReportLeavesUnreached($clover, $root, $tree);
    unlink($clover);

    if ($missed === []) {
        return true;
    }

    fwrite(STDERR, sprintf(<<<'SAID'
        %s does not cover %s, so its mutants cannot be judged by it.

        Not reached: %s

        Every line the suite reaches has to be reached by the tests that hold the
        tree, or `--covered-only` stops mutating it and nothing says so. Add the
        test that runs it to the group.

        SAID, $group, $tree, implode(', ', $missed)));

    return false;
}

/**
 * Every statement in a tree a clover report says nothing reached.
 *
 * A tree with no file in the report at all is answered as unreached as a
 * whole, rather than as nothing missed: a group that runs none of its tree
 * covers none of it, and an empty list would read as the opposite.
 *
 * @return list<string>
 */
function whatTheReportLeavesUnreached(string $clover, string $root, string $tree): array
{
    $report = simplexml_load_file($clover);
    $under = sprintf('%s/%s/', $root, $tree);
    $missed = [];
    $reached = false;

    foreach ($report === false ? [] : $report->xpath('//file') ?? [] as $file) {
        $name = (string) $file['name'];

        if (! str_starts_with($name, $under)) {
            continue;
        }

        $reached = true;

        foreach ($file->xpath('line[@type="stmt"][@count="0"]') ?? [] as $line) {
            $missed[] = sprintf('%s:%s', mb_substr($name, mb_strlen($root) + 1), (string) $line['num']);
        }
    }

    return $reached ? $missed : [sprintf('%s, all of it', $tree)];
}
