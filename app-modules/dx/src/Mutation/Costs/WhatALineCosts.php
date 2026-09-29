<?php

declare(strict_types=1);

namespace Dx\Mutation\Costs;

/**
 * What one line of code costs to mutate, by where it is.
 *
 * A repository's own fit: the same line costs more where the tests that judge
 * it render a screen than where they build a value.
 */
interface WhatALineCosts
{
    /** Runner seconds per line of code in this file. */
    public function secondsPerLineOf(string $file): float;
}
