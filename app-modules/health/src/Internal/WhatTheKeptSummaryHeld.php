<?php

declare(strict_types=1);

namespace Modules\Health\Internal;

use Modules\Kernel\Api\TheHealthSummary;

/**
 * What a kept summary opened to: the summary, or nothing that reads as one.
 *
 * Carried out of the seal's fold, which hands back an object.
 */
final readonly class WhatTheKeptSummaryHeld
{
    public function __construct(public ?TheHealthSummary $summary) {}
}
