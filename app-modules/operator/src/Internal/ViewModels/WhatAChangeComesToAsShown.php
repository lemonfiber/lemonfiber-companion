<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What a proposed change comes to on the machine, as the lines a template draws in order.
 */
final readonly class WhatAChangeComesToAsShown
{
    /**
     * @param list<ALineOfWhatAChangeComesTo> $lines      every line, in the order drawn
     * @param bool                            $interrupts whether it would interrupt a download still coming down
     */
    public function __construct(public array $lines, public bool $interrupts) {}
}
