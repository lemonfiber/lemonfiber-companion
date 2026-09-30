<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function array_slice;
use function count;
use function in_array;

use Modules\Kernel\Api\LookingFor;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Said;
use Modules\Kernel\Api\Scrollback;
use Modules\Kernel\Api\Zone;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatOneLineSays;
use Modules\Operator\Internal\ViewModels\WhatTheServiceTurnedOutToSay;

/**
 * What reading one service's tail produces, as the fields a template reads.
 *
 * **What arrived and what is shown are separate numbers**, because a search
 * narrows the second and must not narrow the first. A window of two hundred
 * filtered to twelve is still a window that stopped at two hundred, and a
 * screen that recomputed the claim from the twelve would tell an operator the
 * search covered everything the service ever said — which is the one lie this
 * screen can tell that somebody would act on.
 *
 * **A run of decorative lines folds into one row.** Two or more lines in a row
 * that hold no letter — the banner a service draws at start-up — become a row
 * saying how many, which opens to show them. One such line alone is left as
 * it is: a fold of one hides nothing and costs a tap. Nothing folds while a
 * search is on, because the lines a search found are the ones somebody asked
 * to see.
 *
 * **Showing from the first error starts the lines there.** The lines before it
 * are left out of what is shown, not out of what arrived, so the window's
 * claim about its edge still holds; the screen says the lines were narrowed and
 * offers every line back.
 */
final readonly class HowAScrollbackReads
{
    /**
     * This device no longer holds a session for that stack.
     *
     * No obstacle, because nothing was met: the app did not get as far as
     * asking. The obstacle screen is where this goes, and the empty keys are what
     * the template branches on.
     */
    public function signedOut(): WhatTheServiceTurnedOutToSay
    {
        return new WhatTheServiceTurnedOutToSay(
            went: HowTheReadingWent::theSessionEnded(),
            lines: [],
            arrived: 0,
            bound: 0,
            isAWindow: false,
            isSearching: false,
        );
    }

    /**
     * The stack answered, and this is the window, narrowed to what was typed.
     *
     * @param list<int> $open             the folds somebody has opened, counted from the top of the window
     * @param bool      $fromTheFirstError whether somebody asked for the lines from the first error on, so the ones before it are left out
     */
    public function this(
        Scrollback $scrollback,
        LookingFor $looking,
        Zone $zone,
        array $open,
        bool $fromTheFirstError = false,
    ): WhatTheServiceTurnedOutToSay {
        $shown = $scrollback->matching($looking);
        $folds = ! $shown->lookingFor()->isSearching();

        $rows = [];
        $run = [];

        foreach ($shown as $line) {
            if ($folds && $line->holdsNoLetters()) {
                $run[] = $line;

                continue;
            }

            $rows = $this->withTheRun($rows, $run, $zone, $open);
            $run = [];
            $rows[] = new HowALineReads()->in($line, $zone);
        }

        $rows = $this->withTheRun($rows, $run, $zone, $open);
        $first = $this->firstErrorIn($rows);
        $narrowed = $fromTheFirstError && $first > 0;

        // Every claim about the edge comes off the window rather than off the
        // rows, which is what keeps a search — or showing from the first error — from quietly
        // narrowing it.
        return new WhatTheServiceTurnedOutToSay(
            went: HowTheReadingWent::itCameBack(),
            lines: $narrowed ? array_slice($rows, $first) : $rows,
            arrived: $shown->howManyArrived(),
            bound: $shown->asked()->figure(),
            isAWindow: $shown->isAWindow(),
            isSearching: $shown->lookingFor()->isSearching(),
            hasAnErrorFurtherDown: ! $narrowed && $first > 0,
            startsAtTheFirstError: $narrowed,
        );
    }

    /**
     * It did not, and this is what the operator met.
     *
     * The keys come off {@see Obstacle}, which owns them — so an obstacle
     * gaining a seventh case needs no edit here and cannot be given a sentence
     * here that disagrees with the one another screen shows.
     *
     * **A credential the stack refused is a signed-out app, not an obstacle.**
     * An identity removed from the household results in a
     * signed-out app at the next refused call and that nothing already loaded
     * goes on being rendered. {@see Obstacle::meansWeAreSignedOut()} draws that
     * line once, so this fold and every one beside it cannot come to disagree
     * about whether somebody is signed in — and a window already fetched is not
     * shown under a sentence about a machine.
     */
    public function met(Obstacle $why): WhatTheServiceTurnedOutToSay
    {
        return new WhatTheServiceTurnedOutToSay(
            went: HowTheReadingWent::somethingStopped($why),
            lines: [],
            arrived: 0,
            bound: 0,
            isAWindow: false,
            isSearching: false,
        );
    }

    /**
     * The rows so far, followed by a run of decorative lines.
     *
     * Folded where there are two or more, and each fold is numbered by how
     * many came before it — so the same window folds the same way on every
     * frame, and the fold somebody opened stays the one that is open.
     *
     * @param list<WhatOneLineSays> $rows
     * @param list<Said>            $run
     * @param list<int>             $open
     *
     * @return list<WhatOneLineSays>
     */
    private function withTheRun(array $rows, array $run, Zone $zone, array $open): array
    {
        if (count($run) < 2) {
            return $this->withTheLines($rows, $run, $zone);
        }

        $fold = $this->foldsIn($rows);
        $isOpen = in_array($fold, $open, strict: true);
        $rows[] = new HowALineReads()->folding(count($run), $fold, $isOpen);

        return $isOpen ? $this->withTheLines($rows, $run, $zone) : $rows;
    }

    /**
     * The rows so far, followed by each of these lines as a row of its own.
     *
     * @param list<WhatOneLineSays> $rows
     * @param list<Said>            $lines
     *
     * @return list<WhatOneLineSays>
     */
    private function withTheLines(array $rows, array $lines, Zone $zone): array
    {
        foreach ($lines as $line) {
            $rows[] = new HowALineReads()->in($line, $zone);
        }

        return $rows;
    }

    /**
     * Where among these rows the first line declaring an error is, or 0 where
     * it is the first row or there is none — either way, nothing to leave out.
     *
     * @param list<WhatOneLineSays> $rows
     */
    private function firstErrorIn(array $rows): int
    {
        foreach ($rows as $at => $row) {
            if ($row->isAnError) {
                return $at;
            }
        }

        return 0;
    }

    /**
     * How many folds are among these rows, which is the number the next one takes.
     *
     * @param list<WhatOneLineSays> $rows
     */
    private function foldsIn(array $rows): int
    {
        $folds = 0;

        foreach ($rows as $row) {
            if ($row->folded > 0) {
                ++$folds;
            }
        }

        return $folds;
    }
}
