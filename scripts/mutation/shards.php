<?php

declare(strict_types=1);

/*
 * How the gate is cut into shards: what each file weighs, and the runs the files are cut into.
 *
 * Part of `scripts/mutation.php`, which requires it.
 */

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
