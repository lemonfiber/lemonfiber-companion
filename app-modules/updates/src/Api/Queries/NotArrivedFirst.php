<?php

declare(strict_types=1);

namespace Modules\Updates\Api\Queries;

use Modules\Kernel\Api\HowAServiceTookIt;
use Modules\Kernel\Api\HowServicesTookIt;

use function usort;

/**
 * What became of each service, with what needs attention first (`N2-R18`).
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
 * grading a situation it cannot see. `N2-R18` has them told apart on the row,
 * which is where the operator reads what to do; it does not ask anybody to rank
 * them, and a screen that did would be putting a guess above the stack's report.
 *
 * **Ties keep the order they arrived in.** `usort` has been stable since PHP
 * 8.0, and the order the stack reports is the order it applied the update —
 * which is information. A service that failed because the one before it failed
 * reads differently the other way round, and re-sorting equals would discard
 * the only evidence of which came first.
 *
 * A query, so it answers with the services rather than with an `Outcome`:
 * asking how to order a list cannot be refused (`M1`).
 */
final readonly class NotArrivedFirst
{
    public function over(HowServicesTookIt $went): HowServicesTookIt
    {
        // Collected by hand rather than with `iterator_to_array`, which wants a
        // `preserve_keys` argument it cannot be wrong about here: this
        // collection always holds a list, so either value produces the same
        // array. An argument that cannot change the answer is a line no test
        // can defend.
        $ordered = [];

        foreach ($went as $took) {
            $ordered[] = $took;
        }

        usort($ordered, $this->whichComesFirst(...));

        // Spread rather than handed over as an array: `D1` keeps arrays out of
        // a published signature, and a collection that took one would be the
        // hole rather than the exception.
        return HowServicesTookIt::these(...$ordered);
    }

    /**
     * Which of two services is read first.
     *
     * The comparison is the whole of it: `false <=> true` is negative, so a
     * service that did not arrive sorts above one that did, and two alike
     * compare equal and keep the order they came in.
     *
     * Written as the comparison rather than as named `BEFORE` and `AFTER`
     * constants, which {@see \Modules\Health\Api\Queries\WorstFirst} has and
     * earns — it compares on two keys and the names are what make the ladder
     * readable. One key needs no ladder, and constants here would be three
     * numbers whose magnitude nothing reads and which nothing could be wrong
     * about.
     */
    private function whichComesFirst(HowAServiceTookIt $one, HowAServiceTookIt $other): int
    {
        return $one->ending()->arrived() <=> $other->ending()->arrived();
    }
}
