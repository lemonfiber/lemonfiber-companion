<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What a proposed change comes to on the machine, as the lines a template draws in order.
 */
final readonly class WhatAChangeComesToAsShown
{
    /** @param list<ALineOfWhatAChangeComesTo> $lines */
    public function __construct(public array $lines) {}
}
