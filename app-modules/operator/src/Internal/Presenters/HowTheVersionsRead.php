<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AGroupOfChanges;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\WhatRunsHere;
use Modules\Operator\Internal\ViewModels\ALineOfTheNotesAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheVersionsTurnedOutToBe;

/**
 * Which versions a stack runs, as the fields a screen draws.
 *
 * `F2`: data in, view model out. The running release's notes are shown only
 * where the stack says they describe this copy; notes not written yet and notes
 * out of step are each said as what they are, and neither is drawn as current.
 */
final readonly class HowTheVersionsRead
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): TheVersionsTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::theSessionEnded());
    }

    /** It did not answer, and this is what the operator met. */
    public function met(Obstacle $why): TheVersionsTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::somethingStopped($why));
    }

    /** The stack answered, and these are the versions it runs. */
    public function these(WhatRunsHere $runs): TheVersionsTurnedOutToBe
    {
        return $runs->running(
            named: fn(Release $release, array $changes): TheVersionsTurnedOutToBe => $runs->notes() === HowTheNotesStand::Current
                ? $this->withTheNotes($runs, $release, $changes)
                : $this->withoutTheNotes($runs, $runs->notes()),
            notNamed: fn(): TheVersionsTurnedOutToBe => $this->withoutTheNotes($runs, $runs->notes()),
        );
    }

    /**
     * The versions, and what the running release changed.
     *
     * @param list<AGroupOfChanges> $changes
     */
    private function withTheNotes(WhatRunsHere $runs, Release $release, array $changes): TheVersionsTurnedOutToBe
    {
        $shown = [];

        foreach ($changes as $group) {
            $shown[] = new ALineOfTheNotesAsShown($group->title(), heading: true);

            foreach ($group as $entry) {
                $shown[] = new ALineOfTheNotesAsShown($entry, heading: false);
            }
        }

        return new TheVersionsTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            lemonfiber: $runs->lemonfiber(),
            stack: $runs->stack(),
            engine: $runs->engine(),
            notesSaid: '',
            notesMeanSaid: '',
            release: $release->version(),
            noticedSaid: $release->theHouseholdWouldNotice() ? 'updates.would_be_noticed' : 'updates.would_not_be_noticed',
            withdrawn: $release->wasWithdrawn(),
            changes: $shown,
        );
    }

    /**
     * The versions, and why the notes are not shown.
     *
     * Notes the stack calls current but names no release for are notes not
     * written for this copy, and said as that.
     */
    private function withoutTheNotes(WhatRunsHere $runs, HowTheNotesStand $notes): TheVersionsTurnedOutToBe
    {
        $stale = $notes === HowTheNotesStand::Stale;

        return new TheVersionsTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            lemonfiber: $runs->lemonfiber(),
            stack: $runs->stack(),
            engine: $runs->engine(),
            notesSaid: $stale ? 'stacks.versions.notes_stale' : 'stacks.versions.notes_pending',
            notesMeanSaid: $stale ? 'stacks.versions.notes_stale_means' : 'stacks.versions.notes_pending_means',
            release: '',
            noticedSaid: '',
            withdrawn: false,
            changes: [],
        );
    }

    /** An answer with nothing in it, for a reading that did not come back. */
    private function nothingFrom(HowTheReadingWent $went): TheVersionsTurnedOutToBe
    {
        return new TheVersionsTurnedOutToBe(
            went: $went,
            lemonfiber: '',
            stack: '',
            engine: '',
            notesSaid: '',
            notesMeanSaid: '',
            release: '',
            noticedSaid: '',
            withdrawn: false,
            changes: [],
        );
    }
}
