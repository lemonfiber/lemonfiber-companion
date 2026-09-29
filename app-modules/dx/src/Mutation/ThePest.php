<?php

declare(strict_types=1);

namespace Dx\Mutation;

/**
 * The Pest a shard runs, and the PHP it runs on.
 *
 * The one seam through which anything is mutated: how a run is invoked is what
 * a verdict depends on, so an implementation of this is part of every proof the
 * gate records.
 */
interface ThePest
{
    /** What `pest --list-groups` prints. */
    public function groups(): string;

    /**
     * Mutate these paths at a floor, against the group where one is named and
     * otherwise against the whole suite; the exit status of the run.
     */
    public function mutate(AMutationRun $run): int;

    /** The clover report of a group run on its own, or an empty string where it did not pass. */
    public function cloverOf(string $group): string;

    /** What the last mutation run recorded of each file it read, as its results file holds it. */
    public function results(): string;

    /** A digest of the PHP that runs Pest: its version, its extensions and its settings. */
    public function environment(): string;
}
