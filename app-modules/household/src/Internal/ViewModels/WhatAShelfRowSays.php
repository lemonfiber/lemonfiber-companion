<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * One row of posters on a member's Home, under the heading it is drawn with.
 *
 * The heading is a key rather than words, so the row is named in the
 * member's language by the template that draws it.
 */
final readonly class WhatAShelfRowSays
{
    /** @param list<WhatOneHoldingSays> $holdings */
    public function __construct(
        public string $heading,
        public array $holdings,
    ) {}
}
