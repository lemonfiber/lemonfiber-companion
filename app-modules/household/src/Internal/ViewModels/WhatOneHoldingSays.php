<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * One row of a member's shelf, flattened for a template.
 *
 * Strings and nothing else, which is what a view model is for: a template
 * reaching into a value object is a template that has to know the domain, and
 * this module's screens are meant to be readable by somebody who does not.
 *
 * **The identifier is not here.** A row a member can see is a row a member can
 * read the name of; what it takes to play it is the core's to hand over and is
 * not something this app composes, so carrying the id into a template would be
 * carrying the one field a template could be tempted to build an address out
 * of.
 */
final readonly class WhatOneHoldingSays
{
    public function __construct(
        public string $titled,
        public string $medium,
        public string $year,
    ) {}
}
