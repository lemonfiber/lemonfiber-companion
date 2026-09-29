<?php

declare(strict_types=1);

namespace Dx\Mutation\Costs;

/**
 * What mutating one file costs a runner, in seconds.
 *
 * It decides only which runner a file goes to, never whether its mutants are
 * run: a floor of 100 admits no offsetting, so a file judged on one runner is
 * judged exactly as it would be beside every other.
 */
interface WhatAFileCosts
{
    public function secondsFor(string $file): float;
}
