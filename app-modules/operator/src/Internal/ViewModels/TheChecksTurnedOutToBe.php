<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What running every check, or a family of them, would disturb, and what the run found once it came back, flattened for a template.
 */
final readonly class TheChecksTurnedOutToBe
{
    /**
     * @param HowTheReadingWent        $went  whether the stack answered, or what stood in the way
     * @param ARunAsShown|null         $run   the run the route names, or null where the stack did not answer
     * @param list<WhatOneFindingSays> $found every finding the run came back with, worst first; empty before it has
     */
    public function __construct(
        public HowTheReadingWent $went,
        public ?ARunAsShown $run = null,
        public array $found = [],
    ) {}

    /**
     * The same reading, with what the run came back with.
     *
     * @param list<WhatOneFindingSays> $found
     */
    public function withWhatTheRunFound(array $found): self
    {
        return new self($this->went, $this->run, $found);
    }
}
