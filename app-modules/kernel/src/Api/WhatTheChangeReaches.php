<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a change to one setting reaches beyond it: the services whose entries it regenerates, the running services it restarts, what it costs, and why a restart did not happen.
 */
final readonly class WhatTheChangeReaches
{
    private function __construct(
        private Services $regenerates,
        private Services $restarts,
        private string $consequence,
        private string $unrestarted,
    ) {}

    /** As the stack wrote it; what it costs and why nothing restarted are blank where it said neither. */
    public static function said(Services $regenerates, Services $restarts, string $consequence, string $unrestarted): self
    {
        return new self($regenerates, $restarts, $consequence, $unrestarted);
    }

    /** Every service whose entry the change regenerates. */
    public function regenerates(): Services
    {
        return $this->regenerates;
    }

    /** Every running service the change restarts. */
    public function restarts(): Services
    {
        return $this->restarts;
    }

    /** What the change costs, in the stack's words, or blank where it costs nothing. */
    public function consequence(): string
    {
        return $this->consequence;
    }

    /** Why the services it restarts were not restarted, in the stack's words, or blank where they were. */
    public function unrestarted(): string
    {
        return $this->unrestarted;
    }
}
