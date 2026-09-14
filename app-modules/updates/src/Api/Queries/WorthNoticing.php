<?php

declare(strict_types=1);

namespace Modules\Updates\Api\Queries;

use Modules\Kernel\Api\Releases;

/**
 * The releases somebody in the house would see the difference from (`N2-R16`).
 *
 * The distinction that makes an update a decision rather than a chore, and the
 * second thing this module knows that the stack does not: the stack says which
 * releases the household would notice, and it does not say that the ones they
 * would are what a screen should lead with. That is a decision about a screen.
 *
 * **It narrows; it does not reorder.** The releases come out in the order they
 * went in, which is the order the stack listed them — newest first, and the
 * order a changelog is read in. Something that both filtered and sorted would
 * make the two impossible to compose, which is the argument
 * {@see \Modules\Health\Api\Queries\InCategory} makes beside its own sort.
 *
 * **Withdrawn ones are already gone.** `N2-R16`'s other half is refused by
 * {@see Releases::worthOffering()} before anything gets here, so this does not
 * repeat it — a second place a withdrawn release could be let through is a
 * second place to get that wrong.
 *
 * A query, so it answers with the releases rather than with an `Outcome`:
 * asking which of a list to lead with cannot be refused (`M1`). One public
 * method, so the class is named for exactly what it does (`M2`) — *is there
 * one* is this answered and asked whether it is empty, not a second entry
 * point that could come to disagree with the first.
 */
final readonly class WorthNoticing
{
    public function over(Releases $releases): Releases
    {
        $noticed = [];

        foreach ($releases as $release) {
            if ($release->theHouseholdWouldNotice()) {
                $noticed[] = $release;
            }
        }

        return Releases::these(...$noticed);
    }
}
