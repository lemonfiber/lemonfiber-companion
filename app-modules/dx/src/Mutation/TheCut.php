<?php

declare(strict_types=1);

namespace Dx\Mutation;

use function array_any;
use function array_filter;
use function array_key_exists;
use function array_key_last;
use function array_keys;
use function array_search;
use function array_sum;
use function array_values;
use function ceil;
use function count;

use Dx\Mutation\Costs\WhatAFileCosts;

use function implode;
use function max;
use function sprintf;

/**
 * The runners the gate is spread over.
 *
 * Every file a floor mutates is weighed by what it costs to mutate, and the
 * files are cut, in path order, into runs of about {@see SECONDS_PER_SHARD}
 * seconds each. A floor of 100 admits no offsetting, so a file judged on one
 * runner is judged exactly as it would be beside every other file at that
 * floor: the cut decides where a mutant runs, not whether it has to be killed.
 * Files of different floors are never cut into one shard, because that is where
 * a shared run would let the stricter carry the looser.
 *
 * Every path a group holds is mutated in one shard of its own, in the order the
 * full run takes them. Those runs are short — the group is a handful of tests —
 * and each is an invocation of its own either way.
 */
final readonly class TheCut
{
    /**
     * The mutation time one shard is cut to. Every shard also pays a checkout,
     * an install and the canary before its first mutant, and the organisation's
     * runners are shared, so a shard cut smaller than this waits for a runner
     * rather than finishing sooner.
     */
    public const int SECONDS_PER_SHARD = 600;

    public function __construct(private WhatAFileCosts $costs) {}

    /**
     * The shards, keyed by the id a runner is handed.
     *
     * @param  array<int, list<string>> $trees the trees each floor mutates against the whole suite, for naming shards
     * @param  array<int, list<string>> $files the files each floor mutates, in path order
     * @param  list<AHeldPath>          $held
     * @return array<int, AShard>
     */
    public function of(array $trees, array $files, array $held): array
    {
        $cut = [];

        foreach ($files as $floor => $paths) {
            $weighed = [];

            foreach ($paths as $path) {
                $weighed[$path] = $this->costs->secondsFor($path);
            }

            foreach (self::intoRuns($weighed) as $run) {
                $cut[] = ['floor' => $floor, 'files' => array_keys($run), 'held' => []];
            }
        }

        if ($held !== []) {
            $cut[] = ['floor' => 0, 'files' => [], 'held' => $held];
        }

        $shards = [];

        foreach ($cut as $at => $shard) {
            $shards[$at + 1] = new AShard($at + 1, self::labelFor($at, $cut, $trees), $shard['floor'], $shard['files'], $shard['held'], []);
        }

        return $shards;
    }

    /**
     * Files, in the order given, cut into consecutive runs of about
     * {@see SECONDS_PER_SHARD} seconds each.
     *
     * The number of runs is the total over that size, rounded up, and each cut
     * falls on the first file that takes its run past an equal share of the
     * total. No run is left empty.
     *
     * @param  array<string, float>       $files
     * @return list<array<string, float>>
     */
    public static function intoRuns(array $files): array
    {
        $total = array_sum($files);
        $count = max(1, (int) ceil($total / self::SECONDS_PER_SHARD));
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
     * What a shard's runner is called: the trees it mutates, each marked with
     * the part it takes where the tree is cut across more than one shard.
     *
     * @param list<array{floor: int, files: list<string>, held: list<AHeldPath>}> $cut
     * @param array<int, list<string>>                                           $trees
     */
    private static function labelFor(int $at, array $cut, array $trees): string
    {
        $shard = $cut[$at];

        if ($shard['held'] !== []) {
            return 'paths a holds: group judges';
        }

        $named = [];

        foreach (array_key_exists($shard['floor'], $trees) ? $trees[$shard['floor']] : [] as $tree) {
            if (! self::treeIn($tree, $shard['files'])) {
                continue;
            }

            $spans = array_keys(array_filter($cut, static fn(array $other): bool => self::treeIn($tree, $other['files'])));

            $named[] = count($spans) === 1
                ? $tree
                : sprintf('%s, part %d of %d', $tree, (int) array_search($at, $spans, strict: true) + 1, count($spans));
        }

        return implode('; ', $named);
    }

    /** @param list<string> $files */
    private static function treeIn(string $tree, array $files): bool
    {
        return array_any($files, static fn(string $file): bool => Paths::under($file, [$tree]));
    }
}
