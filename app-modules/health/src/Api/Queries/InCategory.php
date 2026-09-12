<?php

declare(strict_types=1);

namespace Modules\Health\Api\Queries;

use Modules\Health\Api\Category;
use Modules\Health\Api\Findings;

/**
 * The findings from one family of checks, and nothing else.
 *
 * `Category` says what this is for in its own words — the families exist "so a
 * run can be narrowed to one of them". A screen showing what is wrong with the
 * network has no use for a full report, and an operator working through one
 * area should not have to read past eight others to find out whether they
 * finished.
 *
 * **It narrows; it does not reorder.** The findings come out in the order they
 * went in, which is the order the checks ran — the same information
 * `WorstFirst` is careful to preserve between equals. Something that both
 * filtered and sorted would make the two impossible to compose, because the
 * caller could no longer say which happened first. Reading worst-first within
 * one category is `(new WorstFirst)->over((new InCategory($c))->over($all))`,
 * and that reads in the order it happens.
 *
 * **Empty is an answer.** A category with nothing in it is a category with
 * nothing to say — a family the product recognises where this stack raised
 * nothing, or where the checks that fill it do not exist yet. It is not a
 * missing report and it is not an error, which is why this returns `Findings`
 * rather than anything that could refuse (M1).
 */
final readonly class InCategory
{
    public function __construct(private Category $category) {}

    public function over(Findings $findings): Findings
    {
        // Collected by hand rather than with `iterator_to_array` and
        // `array_filter`, for the reason `WorstFirst` gives: the keys argument
        // cannot be wrong here, so it is a line no test can defend.
        $kept = [];

        foreach ($findings as $finding) {
            if ($finding->category() === $this->category) {
                $kept[] = $finding;
            }
        }

        // Spread rather than handed over as an array: `D1` keeps arrays out of
        // a published signature.
        return Findings::of(...$kept);
    }
}
