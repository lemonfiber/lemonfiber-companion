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
 * **Three arguments, all for CI.** `--list` prints the shards as a JSON array,
 * which is what a workflow matrix reads; `--shard=<id>` runs one of them; and
 * `--shard=<id> --proof` prints what that shard's verdict depends on instead,
 * so a runner can skip a shard whose proof already passed. A
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

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use Tests\Support\MeasuredTree;
use Tests\Support\OurCode;
use Tests\Support\Tree;

// What a line of code costs to mutate on a runner, in seconds, by where it is;
// the longest matching path wins. A line in a screen or a presenter costs more
// than one in a value object, because the tests that judge its mutants render
// the screen. The figures are CI mutation seconds over lines of code mutated,
// fitted to every shard of CI run 36163604500, which mutated every tree. An
// adapter under `sdk/src` costs the most: every one of its mutants runs the
// contract suites that spoil each field of each answer it reads.
//
// A path with no entry costs SECONDS_PER_LINE_ELSEWHERE. These numbers decide
// only which runner a file goes to, never whether its mutants are run.
const SECONDS_PER_LINE = [
    'app-modules/household/src' => 0.82,
    'app-modules/kernel/src' => 0.42,
    'app-modules/operator/src' => 0.13,
    'app-modules/operator/src/Internal/Presenters' => 0.62,
    'app-modules/operator/src/Internal/Screens' => 0.55,
    'app-modules/sdk/src' => 1.20,
    'bridge/src' => 0.44,
];

const SECONDS_PER_LINE_ELSEWHERE = 0.20;

// The mutation time one shard is cut to. Every shard also pays a checkout, an
// install and the canary before its first mutant, since it reads the coverage
// map the tests job wrote rather than running the suite again; and the
// organisation's runners are shared, so a shard cut smaller than this waits
// for a runner rather than finishing sooner.
const SECONDS_PER_SHARD = 600;

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

$root = dirname(__DIR__);

// The same derivation the suite uses, reached the only way a script can reach
// it. `--list` therefore needs an install on the runner that asks for it,
// which is the price of the two of them never disagreeing.
require sprintf('%s/vendor/autoload.php', $root);

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

// What a workflow matrix reads: one entry per runner. An empty array is a
// legitimate answer — no tree holds code yet — and a matrix over it runs
// nothing, which is why the job that aggregates the shards has to treat
// "nothing ran" as a pass rather than as an absence. Slashes unescaped,
// because a label names paths and `bootstrap\/Composition` is what a runner
// would be labelled with otherwise.
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

/**
 * The runners the gate is spread over, keyed by the id `--shard=` names.
 *
 * Every file a floor mutates is weighed by what its lines cost to mutate, and
 * the files are cut, in path order, into runs of about {@see SECONDS_PER_SHARD}
 * seconds each. A
 * floor of 100 admits no offsetting, so a file judged on one runner is judged
 * exactly as it would be beside every other file at that floor: the cut decides
 * where a mutant runs, not whether it has to be killed. Files of different
 * floors are never cut into one shard, because that is where a shared run
 * would let the stricter carry the looser.
 *
 * Every path a group holds is mutated in one shard of its own, in the order
 * the full run takes them. Those runs are short — the group is a handful of
 * tests — and each is an invocation of its own either way.
 *
 * @param  array<int, list<string>>                                  $byFloor
 * @param  list<array{floor: int, path: string, group: string}>      $held
 * @param  array<int, list<string>>                                  $leftOut
 * @param  list<string>|null                                         $reach   the paths a change reaches, or null for all of them
 * @return array<int, array{label: string, floor: int, files: list<string>, held: list<array{floor: int, path: string, group: string}>}>
 */
function shardsOf(array $byFloor, array $held, array $leftOut, ?array $reach): array
{
    $cut = [];

    if ($reach !== null) {
        $held = array_values(array_filter($held, static fn(array $run): bool => under($run['path'], $reach) || array_any($reach, static fn(string $path): bool => under($path, [$run['path']]))));
    }

    foreach ($byFloor as $floor => $trees) {
        $files = filesToMutate($trees, array_key_exists($floor, $leftOut) ? $leftOut[$floor] : []);

        if ($reach !== null) {
            $files = array_filter($files, static fn(string $file): bool => under($file, $reach), ARRAY_FILTER_USE_KEY);
        }

        foreach (cutIntoRuns($files) as $run) {
            $cut[] = ['floor' => $floor, 'files' => array_keys($run), 'held' => []];
        }
    }

    if ($held !== []) {
        $cut[] = ['floor' => 0, 'files' => [], 'held' => $held];
    }

    $shards = [];

    foreach ($cut as $at => $shard) {
        $shards[$at + 1] = [...$shard, 'label' => labelFor($at, $cut, $byFloor)];
    }

    return $shards;
}

/**
 * Every file under these trees, relative to the repository and in path order,
 * each with the seconds its lines cost to mutate. A file a group holds is left
 * out: its shard is the one for held paths.
 *
 * `OurCode::sourceFiles()` is the list the rules read, sorted there. The order
 * is what lets every runner cut the same shards: a directory listing comes
 * back in whatever order the filesystem keeps, and two runners are two
 * filesystems.
 *
 * @param  list<string>       $trees
 * @param  list<string>       $leftOut
 * @return array<string, float>
 */
function filesToMutate(array $trees, array $leftOut): array
{
    $root = sprintf('%s/', Tree::root());
    $files = [];

    foreach (OurCode::sourceFiles() as $file) {
        $relative = mb_substr($file, mb_strlen($root));

        if (under($relative, $trees) && ! under($relative, $leftOut)) {
            $files[$relative] = linesOfCode($file) * secondsPerLine($relative);
        }
    }

    return $files;
}

/**
 * Whether a path is one of these, or inside one of them.
 *
 * @param list<string> $paths
 */
function under(string $file, array $paths): bool
{
    return array_any($paths, fn(string $path): bool => $file === $path || str_starts_with($file, sprintf('%s/', $path)));
}

/**
 * What a line of this file costs to mutate: the entry in SECONDS_PER_LINE for
 * the longest path that holds it, or SECONDS_PER_LINE_ELSEWHERE.
 */
function secondsPerLine(string $file): float
{
    $cost = SECONDS_PER_LINE_ELSEWHERE;
    $matched = '';

    foreach (SECONDS_PER_LINE as $path => $seconds) {
        if (under($file, [$path]) && mb_strlen($path) > mb_strlen($matched)) {
            $cost = $seconds;
            $matched = $path;
        }
    }

    return $cost;
}

/**
 * The lines of a PHP file that hold code: a line with at least one token that
 * is not whitespace, a comment or the opening tag. A line holding only a
 * brace or a semicolon counts for nothing: `token_get_all()` gives those
 * tokens as bare strings, with no line number.
 *
 * With `secondsPerLine()` it is the weight the shards are balanced by, and only
 * that. It does not decide what is mutated.
 */
function linesOfCode(string $file): int
{
    $source = file_get_contents($file);
    $lines = [];

    foreach (token_get_all(is_string($source) ? $source : '') as $token) {
        if (is_array($token) && ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG], strict: true)) {
            $lines[$token[2]] = true;
        }
    }

    return count($lines);
}

/**
 * Files, in the order given, cut into consecutive runs of about
 * {@see SECONDS_PER_SHARD} seconds of mutation each.
 *
 * The number of runs is the total over that size, rounded up, and each cut
 * falls on the first file that takes its run past an equal share of the total.
 * No run is left empty.
 *
 * @param  array<string, float>       $files
 * @return list<array<string, float>>
 */
function cutIntoRuns(array $files): array
{
    $total = array_sum($files);
    $count = max(1, (int) ceil($total / SECONDS_PER_SHARD));
    $share = $total / $count;

    $runs = [[]];
    $weighed = 0;

    foreach ($files as $file => $seconds) {
        $runs[array_key_last($runs)][$file] = $seconds;
        $weighed += $seconds;

        if (count($runs) < $count && $weighed >= $share * count($runs)) {
            $runs[] = [];
        }
    }

    return array_values(array_filter($runs, static fn(array $run): bool => $run !== []));
}

/**
 * What a shard's runner is called: the trees it mutates, each marked with the
 * part it takes where the tree is cut across more than one shard.
 *
 * @param list<array{floor: int, files: list<string>, held: list<array{floor: int, path: string, group: string}>}> $cut
 * @param array<int, list<string>>                                                                                  $byFloor
 */
function labelFor(int $at, array $cut, array $byFloor): string
{
    $shard = $cut[$at];

    if ($shard['held'] !== []) {
        return 'paths a holds: group judges';
    }

    $named = [];

    foreach ($byFloor[$shard['floor']] as $tree) {
        if (! treeIn($tree, $shard['files'])) {
            continue;
        }

        $spans = array_keys(array_filter($cut, static fn(array $other): bool => treeIn($tree, $other['files'])));

        $named[] = count($spans) === 1
            ? $tree
            : sprintf('%s, part %d of %d', $tree, (int) array_search($at, $spans, strict: true) + 1, count($spans));
    }

    return implode('; ', $named);
}

/**
 * Whether any of these files is inside a tree.
 *
 * @param list<string> $files
 */
function treeIn(string $tree, array $files): bool
{
    return array_any($files, fn(string $file): bool => under($file, [$tree]));
}

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
 * The paths a pull request's change reaches, from what it changed since the
 * commit it is measured against; null where every path has to be mutated.
 *
 * - A changed PHP file under a measured tree is mutated.
 * - A change to a module's own tests, or the bridge's, mutates that module's
 *   whole tree, because those tests are what judge its mutants.
 * - A changed test anywhere mutates every file its tests execute, read from
 *   the coverage map the tests job wrote; a deleted test, or no map to read,
 *   mutates everything.
 * - A change to what decides how the gate runs — a manifest, `phpunit.xml`,
 *   the Pest bootstrap, `tests/Support`, the application's bootstrap and
 *   config, this script and the workflow that runs it — mutates everything. A
 *   change to that workflow which only moves the revisions its actions are
 *   pinned at is not one: it mutates nothing by itself.
 * - Anything else — documentation, templates, translations, the lock, other
 *   workflows — mutates nothing by itself.
 *
 * A change can still reach a mutant in a file it did not touch: a template
 * that decides what a screen test sees, or a dependency the lock moved. The
 * run on `main` mutates what was reached since its last commit that passed,
 * and a shard's proof ({@see proofOf()}) covers those files, so a shard whose
 * proof they moved is run again rather than skipped.
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

/**
 * Whether a changed path decides how the gate runs, which mutates every path.
 *
 * The workflow that runs this is one such path, unless all its change did was
 * move the revisions its actions are pinned at.
 */
function decidesHowTheGateRuns(string $root, string $since, string $path): bool
{
    if (preg_match('#^(composer\.json|phpunit\.xml|tests/(Pest|TestCase)\.php|tests/Support/(MeasuredTree|OurCode|Tree|Kind|Module|Imports)\.php|bootstrap/[^/]+|config/.*|scripts/mutation\.php|\.github/workflows/ci\.yml|app-modules/[^/]+/composer\.json|bridge/composer\.json)$#u', $path) !== 1) {
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
 * CI names the map the `tests` job wrote and how long that run took, in
 * `MUTATION_SHARED_COVERAGE` and `MUTATION_SUITE_SECONDS`, so a shard opens on
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

/**
 * Each path a group declares it holds — a measured tree, or a file or directory
 * inside one — by that path.
 *
 * Asked of the suite rather than read out of test sources, so the answer is
 * the groups Pest will actually select by. A group naming a path nothing
 * measures, or one that is not there, is refused rather than ignored: a
 * misspelt path would otherwise be mutated against the whole suite again,
 * correct and slow and with no sign the declaration was never read.
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

        if (! isMeasured($root, $found[2], $measured)) {
            fwrite(STDERR, sprintf(<<<'SAID'
                A test declares the group %s, and %s is not a tree phpunit.xml measures or a path inside one.

                The group names what its tests hold, so the name has to be a measured tree
                or a file or directory in one, spelt as the repository spells it —
                otherwise what it meant is mutated against the whole suite again, and
                nothing says why it is slow.

                SAID, $found[1], $found[2]));

            exit(1);
        }

        $holders[$found[2]] = $found[1];
    }

    return $holders;
}

/**
 * Whether a group reaches every line of what it holds that anything could reach.
 *
 * Measured the way `test:report` measures, over the group alone, and held to
 * every statement in the tree: the coverage floor already holds the suite to
 * all of them, so a statement the group misses is one the suite reaches and
 * the group does not — and `--covered-only` would drop its mutants without a
 * word. Files `phpunit.xml` leaves out of `<source>` are not in the report,
 * which is the same exemption the device-only runloop has everywhere else.
 */
function theGroupCoversWhatItHolds(string $root, string $group, string $tree): bool
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
 * Every statement under a path a clover report says nothing reached.
 *
 * A path with no file in the report at all is answered as unreached as a
 * whole, rather than as nothing missed: a group that runs none of it covers
 * none of it, and an empty list would read as the opposite.
 *
 * @return list<string>
 */
function whatTheReportLeavesUnreached(string $clover, string $root, string $tree): array
{
    $report = simplexml_load_file($clover);
    $exactly = sprintf('%s/%s', $root, $tree);
    $under = sprintf('%s/', $exactly);
    $missed = [];
    $reached = false;

    foreach ($report === false ? [] : $report->xpath('//file') ?? [] as $file) {
        $name = (string) $file['name'];

        if ($name !== $exactly && ! str_starts_with($name, $under)) {
            continue;
        }

        $reached = true;

        foreach ($file->xpath('line[@type="stmt"][@count="0"]') ?? [] as $line) {
            $missed[] = sprintf('%s:%s', mb_substr($name, mb_strlen($root) + 1), (string) $line['num']);
        }
    }

    return $reached ? $missed : [sprintf('%s, all of it', $tree)];
}

/**
 * Whether a path is a measured tree, or a file or directory that exists in one.
 *
 * @param list<string> $measured
 */
function isMeasured(string $root, string $path, array $measured): bool
{
    foreach ($measured as $tree) {
        if ($path === $tree) {
            return true;
        }

        if (str_starts_with($path, sprintf('%s/', $tree)) && file_exists(sprintf('%s/%s', $root, $path))) {
            return true;
        }
    }

    return false;
}

/**
 * What a shard's verdict depends on, as one digest, or empty where that cannot
 * be told.
 *
 * A mutant is killed or not by the tests that run its line, against every line
 * those tests run, under the vendor tree and the files no coverage map records.
 * The digest is taken over the git blob of each of those files, so two commits
 * whose files agree prove the same thing, and a rebase over changes nothing in
 * it reached does not ask the shard again. Without the coverage map the tests
 * job wrote, what judges the shard cannot be told, so nothing is claimed.
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
