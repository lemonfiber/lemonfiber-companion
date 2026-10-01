<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * One reading carried out of an `either()` arm.
 *
 * A class rather than an array, because `either()` answers with an object and
 * the arms must agree on which — the shape the contract suites use for the
 * same reason.
 */
final readonly class WhatAShelfReadingCameTo
{
    /** @param list<string> $rows */
    public function __construct(public array $rows) {}
}
