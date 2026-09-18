<?php

declare(strict_types=1);

namespace Modules\Health\Api\Queries;

use function array_key_exists;

use Modules\Health\Internal\WhatExplainedIt;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;

/**
 * Findings that share a cause, put together, with the cause first.
 *
 * The rule says errors sharing a root cause must be **grouped**, with the cause
 * reported rather than each symptom independently — and the feature's own edge
 * cases gloss it: *multiple errors at once → group by root cause; report the
 * cause first*.
 *
 * Ordering alone is what makes that true on a screen. The wire already carries
 * which check explains which, and the app already showed it — as a line on each
 * row, saying *because of this other thing*, with that other thing somewhere
 * else in a list ordered by severity. An operator then reads four separate
 * problems and goes looking for the one that caused them, which is the reading
 * this query exists to prevent.
 *
 * **It reorders and does not narrow.** The symptoms stay, because a stack with
 * the tunnel down and four services unreachable is worse than one with the
 * tunnel down, and a screen showing only the cause would have lost that. What
 * changes is that they arrive under the thing that explains them.
 *
 * **What it is handed decides the order within a group.** This runs after
 * {@see WorstFirst}, so the symptoms under a cause are already worst-first and
 * stay that way — the same argument `InCategory` makes about narrowing without
 * reordering, which is what lets the three compose in the order a reader would
 * say them: narrow, sort, group.
 *
 * A query, so it answers with the findings rather than with an `Outcome`:
 * asking how to arrange a list cannot be refused (`M1`).
 */
final readonly class TheCauseBeforeItsSymptoms
{
    public function over(Findings $findings): Findings
    {
        $explains = $this->whatEachCheckExplains($findings);
        $arranged = [];

        foreach ($findings as $finding) {
            // A symptom is placed by its cause rather than in its own right, so
            // a run where the cause was graded milder than what it broke still
            // reads cause-first.
            if ($this->explainedBy($finding, $findings)->isExplained()) {
                continue;
            }

            $named = $finding->check()->shown();
            $under = array_key_exists($named, $explains) ? $explains[$named] : [];

            $arranged = [...$arranged, $finding, ...$under];
        }

        return Findings::of(...$arranged);
    }

    /**
     * Every finding that names a given check as what explains it.
     *
     * Only where that check is one this run reported. A finding pointing at a
     * check nobody ran has nothing to sit under, and is left where it was.
     *
     * @return array<string, list<Finding>>
     */
    private function whatEachCheckExplains(Findings $findings): array
    {
        $explains = [];

        foreach ($findings as $finding) {
            $cause = $this->explainedBy($finding, $findings);

            if ($cause->isExplained()) {
                $explains[$cause->check][] = $finding;
            }
        }

        return $explains;
    }

    /**
     * What explains this finding, where the same run reported it.
     *
     * Answered against the run rather than against the finding alone, because
     * *the tunnel is down* is only a cause worth arranging under if the tunnel
     * is one of the things on the screen.
     */
    private function explainedBy(Finding $finding, Findings $findings): WhatExplainedIt
    {
        $named = $finding->whatExplainsIt()->either(
            alone: static fn(): WhatExplainedIt => WhatExplainedIt::nothing(),
            explained: static fn(Check $check): WhatExplainedIt => WhatExplainedIt::theCheck($check),
        );

        return $this->isOnTheScreen($named, $findings) ? $named : WhatExplainedIt::nothing();
    }

    /**
     * Whether the check a finding points at is one the run has.
     *
     * The guard a caller would write first — *did anything explain this at
     * all* — is not written, because it cannot be wrong here and a branch that
     * cannot be wrong is a branch no test can hold to account. Nothing names
     * the empty string, and a check's name is never blank, so a finding that
     * stands on its own falls out of the search rather than being steered
     * around it.
     */
    private function isOnTheScreen(WhatExplainedIt $named, Findings $findings): bool
    {
        foreach ($findings as $other) {
            if ($other->check()->shown() === $named->check) {
                return true;
            }
        }

        return false;
    }

}
