<?php

declare(strict_types=1);

namespace Dx\Mutation;

/**
 * What the gate asks about the repository it runs in: where tests live, which
 * paths decide how it runs, how a group says what it holds.
 *
 * Everything here is a convention of one repository rather than of mutation
 * testing, so the gate asks rather than assumes, and a repository answers once.
 */
interface TheLayout
{
    /** Whether a changed path decides how the gate runs, so that every path is mutated. */
    public function decidesHowTheGateRuns(string $path): bool;

    /** Whether all a change to this path did was move the revisions its actions are pinned at. */
    public function onlyPinsMoved(string $path, string $change): bool;

    /** Whether a path is PHP under a directory tests live in: a test, or support for tests. */
    public function isATest(string $path): bool;

    /** Whether a path is a file of test cases. */
    public function isATestCase(string $path): bool;

    /** Whether a path is support shared by every suite, which reaches the tests that name it. */
    public function isSharedTestSupport(string $path): bool;

    /**
     * The trees a changed test reaches without a coverage map: a module's tests
     * judge that module's code.
     *
     * @return list<string>
     */
    public function treesATestJudges(string $path): array;

    /** Whether a path is under a directory tests live in. */
    public function isUnderTests(string $path): bool;

    /**
     * Whether a path decides what the gate mutates rather than how a mutant is
     * run, so that a proof reads it only where something in the proof names it.
     */
    public function decidesOnlyWhatIsMutated(string $path): bool;

    /** Whether a path is a build script, which nothing a mutant runs loads. */
    public function isABuildScript(string $path): bool;

    /**
     * Files a proof reads only where a string in it names them, by a pattern
     * matching their paths, each with the words that name them.
     *
     * @return array<string, list<string>>
     */
    public function readWhereNamed(): array;

    /** The workflow that runs the gate. */
    public function workflow(): string;

    /** The job in that workflow that runs a shard. */
    public function shardJob(): string;

    /** The group every shard's opening run is. */
    public function canary(): string;

    /** The path a group of the suite holds, or an empty string where it holds none. */
    public function pathHeldBy(string $group): string;
}
