<?php

declare(strict_types=1);

namespace Modules\Health\Api\Queries;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Severity;

use function usort;

/**
 * The findings, worst first — which is the order a screen has to show them in.
 *
 * The one decision this module makes about a report that the server has not
 * already made for it. The server sends `overall` and it sends the findings in
 * the order the checks ran; what it does not send is the order a person should
 * read them in, because that is a decision about a screen.
 *
 * **How much it costs first, then the verdict.** Findings are ordered by
 * severity and nothing else, and the requirement is right about which
 * side decides: severity is the engine's own grading of what a finding puts at
 * risk, and a screen ranking the verdict above that grading would be this app
 * deciding a judgement already made does not count. A warning graded `critical`
 * is the engine saying data or something outside the machine is at risk; a
 * failure graded `advisory` is a broken thing that costs nothing. An operator
 * reading down the list meets them in that order.
 *
 * **The verdict breaks the tie**, because two findings the engine graded alike
 * are still not the same thing — one that failed is not working where one that
 * warned is working badly — and `Conclusion`'s declaration order already says
 * which of those belongs higher.
 *
 * The words are the engine's, not this module's. A screen working severity out
 * for itself would be a second opinion about a judgement already made, which is
 * the argument {@see \Modules\Kernel\Api\Overall} makes about a whole run.
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
            $this->costOf($one)->isWorseThan($this->costOf($other)) => self::BEFORE,
            $this->costOf($other)->isWorseThan($this->costOf($one)) => self::AFTER,
            $one->conclusion()->isWorseThan($other->conclusion()) => self::BEFORE,
            $other->conclusion()->isWorseThan($one->conclusion()) => self::AFTER,
            default => self::TOGETHER,
        };
    }

    /**
     * What this finding costs, for the arms that say.
     *
     * `Advisory` for the two arms that carry no severity, which is the honest
     * floor rather than a guess: a check that passed has nothing to cost, and
     * one that could not run has no judgement to report.
     *
     * The floor is load-bearing now that this is the first key, and it is
     * right at the bottom: a check nobody graded must not be sorted above one
     * the engine graded as mattering. Where two ungraded findings meet, they
     * tie here and the verdict separates them, which is the only thing left
     * that can.
     */
    private function costOf(Finding $finding): Severity
    {
        return $finding->said()->either(
            nothingWrong: static fn(): Severity => Severity::Advisory,
            wentWrong: static fn(
                Code $code,
                string $meaning,
                Remedies $remedies,
                Severity $severity,
            ): Severity => $severity,
            couldNotSay: static fn(): Severity => Severity::Advisory,
        );
    }
}
