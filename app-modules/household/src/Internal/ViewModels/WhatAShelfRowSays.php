<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use function array_slice;

use Closure;

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

    /**
     * The row, its first posters drawn with their pictures, each from the source `$from` answers for the title it plays.
     *
     * A poster that plays nothing is a preview's and is left lettered.
     *
     * @param Closure(string): string $from
     */
    public function pictured(int $first, Closure $from): self
    {
        $posters = [];

        foreach (array_slice($this->posters, 0, $first) as $poster) {
            $posters[] = $poster->plays === '' ? $poster : $poster->pictured($from($poster->plays));
        }

        return new self($this->heading, [...$posters, ...array_slice($this->posters, $first)]);
    }
}
