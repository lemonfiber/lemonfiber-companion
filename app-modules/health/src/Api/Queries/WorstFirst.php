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
 * The findings, worst first — which is the order `N2` asks a screen to show.
 *
 * The one decision this module makes about a report that the server has not
 * already made for it. The server sends `overall` and it sends the findings in
 * the order the checks ran; what it does not send is the order a person should
 * read them in, because that is a decision about a screen.
 *
 * **The verdict first, then how much it costs.** Two checks that both failed
 * are not equally urgent, and the engine says which is worse: a `critical` puts
 * data or something outside the machine at risk where an `error` is a broken
 * thing. Read as a second key rather than a first, because a failure that is
 * merely an error still outranks a warning that is critical — the verdict is
 * whether the thing works and severity is what the answer costs.
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
            $one->conclusion()->isWorseThan($other->conclusion()) => self::BEFORE,
            $other->conclusion()->isWorseThan($one->conclusion()) => self::AFTER,
            $this->costOf($one)->isWorseThan($this->costOf($other)) => self::BEFORE,
            $this->costOf($other)->isWorseThan($this->costOf($one)) => self::AFTER,
            default => self::TOGETHER,
        };
    }

    /**
     * What this finding costs, for the arms that say.
     *
     * `Advisory` for the two arms that carry no severity, which is the honest
     * floor rather than a guess: a check that passed has nothing to cost, and
     * one that could not run has no judgement to report. Neither can be reached
     * across a conclusion boundary anyway — this is only ever asked of two
     * findings the verdict already tied — so the value is a floor rather than a
     * ranking of something that was never graded.
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
