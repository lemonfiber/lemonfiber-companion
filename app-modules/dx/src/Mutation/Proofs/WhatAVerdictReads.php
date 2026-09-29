<?php

declare(strict_types=1);

namespace Dx\Mutation\Proofs;

use function array_diff;
use function array_fill_keys;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;

use Dx\Mutation\TheCoverageMap;
use Dx\Mutation\TheLayout;

use function hash;
use function implode;
use function sort;
use function sprintf;

/**
 * Everything a mutation verdict on one unit can depend on, as one digest.
 *
 * A unit is a file mutated against the tests that cover it, or a path a group
 * judges. Its verdict at a floor of 100 is its own: no mutant in it is offset by
 * another file. So a verdict that held once holds again for as long as nothing
 * it reads has changed, and the digest names that state — two runs that compute
 * the same digest for a unit asked the same question of the same material.
 *
 * What goes in is everything, less what is shown to be beside the point
 * ({@see WhatEachPathIs}):
 *
 * - Every file that is not a test, by its git blob: the code, templates,
 *   translations, configuration, manifests, the lock, the test configuration,
 *   the patches to Pest and whatever runs a mutant. All of the code, not only
 *   what the unit's tests execute, because a test can depend on code no
 *   coverage map says it ran — a constant, a property's default, an attribute
 *   read by reflection, a class found by scanning a directory.
 * - The job that runs a shard, as it runs ({@see TheWorkflowAsItRuns}). The PHP
 *   its actions install is in the environment instead.
 * - Of the tests, the files that judge this unit ({@see WhatJudgesAFile}) and
 *   what the coverage map says ran each of its lines; the support those files
 *   name, and the support that names in turn; and every test in the canary
 *   group, which every shard opens on.
 * - Files read only where named, where a string in any of that names them.
 * - The runtime, the floor, and the unit itself.
 */
final readonly class WhatAVerdictReads
{
    /** Changes whenever what a digest means changes, so no older proof is read as a newer one. */
    private const string FORMAT = 'mutation proof 1';

    /**
     * @param array<string, string> $tracked every file, by its blob
     * @param list<string>          $judges  every file of test cases the coverage map knows
     * @param array<string, true>   $global  the paths in every digest
     */
    private function __construct(
        private string $everyVerdict,
        private array $tracked,
        private WhatJudgesAFile $judging,
        private WhatTheTestsLeanOn $leaning,
        private array $judges,
        private array $global,
    ) {}

    /**
     * @param array<string, string>           $tracked     every file git tracks, by its blob
     * @param array<string, WhatAPhpFileSays> $php         every PHP file among them, read
     * @param string                          $workflow    what the workflow that runs the gate holds
     * @param string                          $environment a digest of the PHP a shard runs on
     */
    public static function of(array $tracked, array $php, TheCoverageMap $map, TheLayout $layout, string $workflow, string $environment): self
    {
        $tracked = self::withTheWorkflowAsItRuns($tracked, $workflow, $layout);
        $paths = WhatEachPathIs::of(array_keys($tracked), $php, $map->tests, $layout);
        $leaning = WhatTheTestsLeanOn::of($php, $paths->leanedOn, $paths->named, $layout->readWhereNamed());

        // What runs beside every test and so reaches what it names: context PHP
        // that is neither a build script nor a test no mutant is run against.
        $besideEveryTest = array_values(array_filter(
            $paths->context,
            static fn(string $path): bool => array_key_exists($path, $php) && ! $layout->isABuildScript($path) && ! $layout->isATestCase($path),
        ));
        $canaries = $leaning->mentioning($paths->judges, [$layout->canary()]);
        $global = array_fill_keys([...$paths->context, ...$leaning->from([...$besideEveryTest, ...$canaries])], true);

        return new self(
            everyVerdict: hash('sha256', sprintf("%s\n%s\n%s", self::FORMAT, $environment, self::blobsOf(array_keys($global), $tracked))),
            tracked: $tracked,
            judging: new WhatJudgesAFile($map->lines, $paths->judges),
            leaning: $leaning,
            judges: $paths->judges,
            global: $global,
        );
    }

    /** The digest of one file mutated against the tests that cover it. */
    public function ofFile(string $file, int $floor): string
    {
        return $this->digest(sprintf("file %s\n%s", $file, $this->judging->linesOf($file)), $floor, $this->judging->of($file) ?? $this->judges);
    }

    /** The digest of a path a group judges, which may be any test. */
    public function ofHeld(string $path, string $group, int $floor): string
    {
        return $this->digest(sprintf('held %s by %s', $path, $group), $floor, $this->judges);
    }

    /** @param list<string> $judges */
    private function digest(string $unit, int $floor, array $judges): string
    {
        $read = array_values(array_diff($this->leaning->from($judges), array_keys($this->global)));

        return hash('sha256', sprintf("%s\nfloor %d\n%s\n%s", $this->everyVerdict, $floor, $unit, self::blobsOf($read, $this->tracked)));
    }

    /**
     * The files git tracks, with the workflow's blob replaced by a digest of
     * the job that runs a shard.
     *
     * @param  array<string, string> $tracked
     * @return array<string, string>
     */
    private static function withTheWorkflowAsItRuns(array $tracked, string $workflow, TheLayout $layout): array
    {
        if (! array_key_exists($layout->workflow(), $tracked)) {
            return $tracked;
        }

        return [...$tracked, $layout->workflow() => sprintf('as-it-runs:%s', hash('sha256', TheWorkflowAsItRuns::of($workflow, $layout->shardJob())))];
    }

    /**
     * Each path with its blob, one per line, in path order.
     *
     * @param list<string>          $paths
     * @param array<string, string> $tracked
     */
    private static function blobsOf(array $paths, array $tracked): string
    {
        $paths = array_values(array_unique($paths));
        sort($paths);

        return implode("\n", array_map(static fn(string $path): string => sprintf('%s %s', array_key_exists($path, $tracked) ? $tracked[$path] : 'untracked', $path), $paths));
    }
}
