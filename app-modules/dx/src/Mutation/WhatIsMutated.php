<?php

declare(strict_types=1);

namespace Dx\Mutation;

use function array_any;
use function array_filter;
use function array_key_exists;
use function array_values;
use function sort;
use function sprintf;

/**
 * Which trees are mutated at which floor, and which paths a group judges apart.
 *
 * Trees that share a floor share a run. A floor of 100 admits no offsetting
 * between them — one surviving mutant anywhere in the path list drops the score
 * below 100 and fails — so grouping costs nothing in rigour and saves a full
 * suite pass per tree. A tree whose floor differs gets its own run, because
 * that is exactly where a shared one would let the stricter tree carry the
 * looser.
 *
 * A held path is mutated in a run of its own: a group narrows every tree in an
 * invocation, so one sharing a run would narrow its neighbours. The tree it is
 * in leaves it out, so no mutant is judged twice or by the wrong tests.
 */
final readonly class WhatIsMutated
{
    /**
     * @param array<int, list<string>> $byFloor  the trees mutated against the whole suite, by floor
     * @param list<AHeldPath>          $held     the paths a group judges
     * @param array<int, list<string>> $leftOut  the held paths inside each floor's trees
     * @param list<string>             $said     a line for each tree that is not mutated, and why
     * @param list<string>             $sources  every source file, relative to the repository, in path order
     */
    private function __construct(
        public array $byFloor,
        public array $held,
        public array $leftOut,
        public array $said,
        private array $sources,
    ) {}

    /**
     * @param list<ATree>           $trees   every tree the suite measures
     * @param array<string, string> $holders the group that holds each held path, by path
     * @param list<string>          $sources every source file, relative to the repository
     */
    public static function of(array $trees, array $holders, array $sources): self
    {
        sort($sources);
        $mutated = new self([], [], [], [], $sources);

        foreach ($trees as $tree) {
            $mutated = $mutated->with($tree, $holders);
        }

        return $mutated;
    }

    public function isEmpty(): bool
    {
        return $this->byFloor === [] && $this->held === [];
    }

    /**
     * Every file a floor mutates against the whole suite, in path order.
     *
     * @return list<string>
     */
    public function filesAt(int $floor): array
    {
        $trees = array_key_exists($floor, $this->byFloor) ? $this->byFloor[$floor] : [];
        $leftOut = array_key_exists($floor, $this->leftOut) ? $this->leftOut[$floor] : [];

        return array_values(array_filter($this->sources, static fn(string $file): bool => Paths::under($file, $trees) && ! Paths::under($file, $leftOut)));
    }

    /** @param array<string, string> $holders */
    private function with(ATree $tree, array $holders): self
    {
        if ($tree->floor === null) {
            throw TheGateCannotRun::because(sprintf(<<<'SAID'
                %s is measured and %s declares no mutation floor.

                Add it to the manifest nearest that tree, beside the kind where there is one:
                    "extra": { "lemonfiber": { "floors": { "mutation": 100 } } }

                There is no default on purpose — a tree that inherits one is exempt from
                the decision rather than held to it. G7 reports the same omission in the
                test suite, so this should already have failed there.

                SAID, $tree->path, $tree->manifest));
        }

        $unmutated = $this->whyNotMutated($tree);

        if ($unmutated !== '') {
            return new self($this->byFloor, $this->held, $this->leftOut, [...$this->said, $unmutated], $this->sources);
        }

        return $this->holding($tree->path, $tree->floor, $holders);
    }

    /**
     * Why a tree is not mutated at all, or nothing where it is.
     *
     * Said out loud rather than skipped in silence, because a tree that was
     * never mutated and a tree with nothing left to kill print the same way —
     * which is nothing at all. A floor of zero is a declared position, and the
     * position is that manifest's own: it says what holds those decisions.
     */
    private function whyNotMutated(ATree $tree): string
    {
        if ($tree->floor === 0) {
            return sprintf('  %s: mutation floor is 0 — %s', $tree->path, $tree->whyZero === '' ? 'and the manifest says nothing about why' : $tree->whyZero);
        }

        return array_any($this->sources, static fn(string $file): bool => Paths::under($file, [$tree->path]))
            ? ''
            : sprintf('  %s: no code yet, nothing to mutate', $tree->path);
    }

    /** @param array<string, string> $holders */
    private function holding(string $tree, int $floor, array $holders): self
    {
        $held = $this->held;
        $leftOut = $this->leftOut;

        foreach ($holders as $path => $group) {
            if (Paths::under($path, [$tree])) {
                $held[] = new AHeldPath($path, $group, $floor);
                $leftOut[$floor][] = $path;
            }
        }

        $byFloor = $this->byFloor;

        if (! array_key_exists($tree, $holders)) {
            $byFloor[$floor][] = $tree;
        }

        return new self($byFloor, $held, $leftOut, $this->said, $this->sources);
    }
}
