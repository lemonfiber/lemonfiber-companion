<?php

declare(strict_types=1);

namespace Modules\Health\Api\Queries;

use function array_flip;
use function array_map;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Severity;
use UnitEnum;

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
 * asking how to order a list cannot be refused.
 */
final readonly class WorstFirst
{
    public function over(Findings $findings): Findings
    {
        $ordered = [];

        foreach ($findings as $finding) {
            $ordered[] = $finding;
        }

        usort($ordered, $this->whichComesFirst(...));

        return Findings::of(...$ordered);
    }

    private function whichComesFirst(Finding $one, Finding $other): int
    {
        return $this->rankOf($one) <=> $this->rankOf($other);
    }

    /**
     * Where a finding ranks: by what it costs, then by its verdict, each counted from the worst.
     *
     * @return array{int, int}
     */
    private function rankOf(Finding $finding): array
    {
        return [$this->countedFromTheWorst($this->costOf($finding), Severity::cases()), $this->countedFromTheWorst($finding->conclusion(), Conclusion::cases())];
    }

    /**
     * How many cases are worse than this one, in an enum declared worst first.
     *
     * @param list<UnitEnum> $cases
     */
    private function countedFromTheWorst(UnitEnum $case, array $cases): int
    {
        return array_flip(array_map(static fn(UnitEnum $one): string => $one->name, $cases))[$case->name];
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
