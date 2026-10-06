<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * One row of posters on a member's Home, under the heading it is drawn with:
 * titles on the shelf, or their own requests.
 *
 * The heading is a key rather than words, so the row is named in the
 * member's language by the template that draws it.
 */
final readonly class WhatAShelfRowSays
{
    /** @param list<WhatOnePosterSays> $posters */
    public function __construct(
        public string $heading,
        public array $posters,
    ) {}
}
