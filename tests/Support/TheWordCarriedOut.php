<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * One line carried out of an arm, for a test to compare.
 *
 * A type's `either()` hands back whatever its arms build, and an arm has to
 * build an object; a test asking which arm ran builds this one and reads
 * `said`. One carrier for every such test, so no test file declares a class of
 * its own for it.
 */
final readonly class TheWordCarriedOut
{
    public function __construct(public string $said) {}
}
