<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a change to settings reaches beyond them: the services whose entries it regenerates, the running services it restarts, the services held back it starts, those it leaves held back, and why a restart did not happen.
 */
final readonly class WhatTheChangeReaches
{
    private function __construct(
        private Services $regenerates,
        private Services $restarts,
        private Services $starts,
        private TheServicesWaiting $waiting,
        private string $unrestarted,
    ) {}

    /** As the stack wrote it; why nothing restarted is blank where it said nothing of it. */
    public static function said(Services $regenerates, Services $restarts, Services $starts, TheServicesWaiting $waiting, string $unrestarted): self
    {
        return new self($regenerates, $restarts, $starts, $waiting, $unrestarted);
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

    /** Every service held back the change starts, having given the last thing it waited for. */
    public function starts(): Services
    {
        return $this->starts;
    }

    /** Every service held back once the change is made, with what it waits for. */
    public function leavesWaiting(): TheServicesWaiting
    {
        return $this->waiting;
    }

    /** Why the services it restarts were not restarted, in the stack's words, or blank where they were. */
    public function unrestarted(): string
    {
        return $this->unrestarted;
    }
}
