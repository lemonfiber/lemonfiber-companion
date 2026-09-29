<?php

declare(strict_types=1);

namespace Dx\Mutation\Costs;

use function array_key_exists;
use function array_map;
use function array_sum;
use function round;

/**
 * What mutating a file took the last time a shard mutated it, and an estimate
 * for a file no shard has.
 *
 * A shard records how long each of its mutants ran and how long it spent
 * mutating in all, and a file's cost is its mutants' share of that time: the
 * mutants run side by side, so their durations add up to a multiple of the
 * time the runner was held, and the share turns them back into runner time.
 * That is measured where the estimate is a fit — a file whose covered lines
 * produce no mutant costs close to nothing, where lines of code would have sent
 * it a whole runner.
 */
final readonly class SecondsMeasured implements WhatAFileCosts
{
    /** @param array<string, float> $seconds each measured file's runner seconds, by path */
    public function __construct(public array $seconds, private WhatAFileCosts $otherwise) {}

    public function secondsFor(string $file): float
    {
        return array_key_exists($file, $this->seconds) ? $this->seconds[$file] : $this->otherwise->secondsFor($file);
    }

    /**
     * Each file's share of the time a run spent mutating, from how long each of
     * its mutants ran.
     *
     * @param  array<string, float> $mutantSeconds each file's mutants' durations added up, by path
     * @return array<string, float>
     */
    public static function sharesOf(array $mutantSeconds, float $wall): array
    {
        $total = array_sum($mutantSeconds);

        if ($total <= 0.0) {
            return array_map(static fn(float $seconds): float => 0.0, $mutantSeconds);
        }

        return array_map(static fn(float $seconds): float => round($seconds / $total * $wall, 2), $mutantSeconds);
    }
}
