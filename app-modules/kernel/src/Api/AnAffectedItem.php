<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One thing the health summary counts as wrong, and what to do about it.
 *
 * Everything the core says about it, and nothing the app adds: which check
 * raised it, how bad it is, what is wrong in one line, what that costs the
 * operator, what to try, most likely first, and what else is wrong because of
 * it.
 */
final readonly class AnAffectedItem
{
    private function __construct(
        private Check $check,
        private Severity $severity,
        private string $summary,
        private string $meaning,
        private Remedies $remedies,
        private WhatFollowedFromIt $downstream,
    ) {}

    public static function of(
        Check $check,
        Severity $severity,
        string $summary,
        string $meaning,
        Remedies $remedies,
        WhatFollowedFromIt $downstream,
    ): self {
        return new self($check, $severity, $summary, $meaning, $remedies, $downstream);
    }

    public function check(): Check
    {
        return $this->check;
    }

    public function severity(): Severity
    {
        return $this->severity;
    }

    public function summary(): string
    {
        return $this->summary;
    }

    public function meaning(): string
    {
        return $this->meaning;
    }

    public function remedies(): Remedies
    {
        return $this->remedies;
    }

    public function downstream(): WhatFollowedFromIt
    {
        return $this->downstream;
    }
}
