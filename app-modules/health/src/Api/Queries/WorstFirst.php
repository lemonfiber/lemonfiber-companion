<?php

declare(strict_types=1);

namespace Modules\Health\Api\Queries;

use Modules\Health\Api\Finding;
use Modules\Health\Api\Findings;

use function usort;

/**
 * The findings, worst first — which is the order `N2` asks a screen to show.
 *
 * The one decision this module makes about a report that the server has not
 * already made for it. The server sends `overall` and it sends the findings in
 * the order the checks ran; what it does not send is the order a person should
 * read them in, because that is a decision about a screen.
 *
 * **Ties keep the order they arrived in.** `usort` has been stable since PHP
 * 8.0, and the order a report arrives in is the order the checks ran — which
 * is information. Two findings in the same category where one caused the other
 * read differently the other way round, and re-sorting equals would discard
 * the only evidence of which came first.
 *
 * A query, so it answers with the findings rather than with an `Outcome`:
 * asking how to order a list cannot be refused (M1).
 */
final readonly class WorstFirst
{
    /** What `usort` wants back, named so that the comparison reads as English. */
    private const int BEFORE = -1;

    private const int TOGETHER = 0;

    private const int AFTER = 1;

    public function over(Findings $findings): Findings
    {
        // Collected by hand rather than with `iterator_to_array`, which wants
        // a `preserve_keys` argument it cannot be wrong about here: `Findings`
        // always holds a list, so either value produces the same array. An
        // argument that cannot change the answer is a line no test can defend.
        $ordered = [];

        foreach ($findings as $finding) {
            $ordered[] = $finding;
        }

        usort($ordered, $this->whichComesFirst(...));

        // Spread rather than handed over as an array: `D1` keeps arrays out
        // of a published signature, and a collection that took one would be the
        // hole rather than the exception.
        return Findings::of(...$ordered);
    }

    private function whichComesFirst(Finding $one, Finding $other): int
    {
        return match (true) {
            $one->conclusion()->isWorseThan($other->conclusion()) => self::BEFORE,
            $other->conclusion()->isWorseThan($one->conclusion()) => self::AFTER,
            default => self::TOGETHER,
        };
    }
}
