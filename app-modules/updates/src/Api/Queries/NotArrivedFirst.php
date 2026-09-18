<?php

declare(strict_types=1);

namespace Modules\Updates\Api\Queries;

use Modules\Kernel\Api\HowServicesTookIt;

/**
 * What became of each service, with what needs attention first.
 *
 * The one decision this module makes about an applied update that the stack has
 * not already made for it. The stack sends what became of every service and it
 * sends them in the order it touched them; what it does not send is the order a
 * person should read them in, because that is a decision about a screen.
 *
 * **One key, and it is not a ranking of the failures.** A service either arrived
 * where the operator wanted it or it did not, and the ones that did not go
 * first. What this deliberately does **not** do is order *not fetched*, *not
 * started* and *not reached* against each other: those are a network, a service
 * and a machine, and deciding which of the three matters most would be this app
 * grading a situation it cannot see. They are told apart on the row,
 * which is where the operator reads what to do; it does not ask anybody to rank
 * them, and a screen that did would be putting a guess above the stack's report.
 *
 * **A partition, not a sort, and that is the whole reason it is safe.** Two
 * lists filled in one pass and joined, so each keeps the order the stack
 * reported — which is the order it applied the update, and which is
 * information: a service that failed because the one before it failed reads
 * differently the other way round.
 *
 * A comparator would have had to answer *how much* one row outranks another,
 * and there is no such quantity here. It would have been a number no test could
 * defend — a `-1` written as `-2` sorts identically — and a magnitude invites
 * the ranking the paragraph above refuses.
 *
 * A query, so it answers with the services rather than with an `Outcome`:
 * asking how to order a list cannot be refused (`M1`).
 */
final readonly class NotArrivedFirst
{
    public function over(HowServicesTookIt $went): HowServicesTookIt
    {
        $wanting = [];
        $arrived = [];

        foreach ($went as $took) {
            if ($took->ending()->arrived()) {
                $arrived[] = $took;

                continue;
            }

            $wanting[] = $took;
        }

        // Spread rather than handed over as an array: `D1` keeps arrays out of
        // a published signature, and a collection that took one would be the
        // hole rather than the exception.
        return HowServicesTookIt::these(...$wanting, ...$arrived);
    }
}
