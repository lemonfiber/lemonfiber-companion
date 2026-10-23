<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What running every check, or a family of them, would disturb, and what the run found once it came back, flattened for a template.
 */
final readonly class TheChecksTurnedOutToBe
{
    /**
     * @param HowTheReadingWent         $went       whether the stack answered, or what stood in the way
     * @param string                    $titleSaid  the catalogue key for what the run covers
     * @param string                    $familySaid the catalogue key for the family a family's run covers, or empty
     * @param list<ADisturbanceAsShown> $disturbing every check the run would run that disturbs something, in the stack's order
     * @param list<WhatOneFindingSays>  $found      every finding the run came back with, worst first; empty before it has
     * @param bool                      $runnable   whether the route names a run this app can send
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $titleSaid,
        public string $familySaid,
        public array $disturbing,
        public array $found,
        public bool $runnable,
    ) {}

    /** The same reading, with what the run came back with. */
    public function withWhatTheRunFound(TheChecksTurnedOutToBe $ran): self
    {
        return new self($this->went, $this->titleSaid, $this->familySaid, $this->disturbing, $ran->found, $this->runnable);
    }
}
