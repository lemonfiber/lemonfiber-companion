<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AGroupOfChanges;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\WhatRunsHere;
use Modules\Operator\Internal\ViewModels\AGroupOfTheNotesAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheReleaseNotesAsShown;
use Modules\Operator\Internal\ViewModels\TheVersionsAsRead;
use Modules\Operator\Internal\ViewModels\TheVersionsTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatWithheldNotesSay;

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
        return new TheVersionsTurnedOutToBe(HowTheReadingWent::theSessionEnded());
    }

    /** It did not answer, and this is what the operator met. */
    public function met(Obstacle $why): TheVersionsTurnedOutToBe
    {
        return new TheVersionsTurnedOutToBe(HowTheReadingWent::somethingStopped($why));
    }

    /** The stack answered, and these are the versions it runs. */
    public function these(WhatRunsHere $runs): TheVersionsTurnedOutToBe
    {
        $notes = $runs->running(
            named: fn(Release $release, array $changes): TheReleaseNotesAsShown|WhatWithheldNotesSay => $runs->notes() === HowTheNotesStand::Current
                ? $this->notes($release, $changes)
                : $this->withheld($runs->notes()),
            notNamed: fn(): WhatWithheldNotesSay => $this->withheld($runs->notes()),
        );

        return new TheVersionsTurnedOutToBe(
            HowTheReadingWent::itCameBack(),
            new TheVersionsAsRead($runs->lemonfiber(), $runs->stack(), $runs->engine(), $notes),
        );
    }

    /**
     * What the running release changed.
     *
     * @param list<AGroupOfChanges> $changes
     */
    private function notes(Release $release, array $changes): TheReleaseNotesAsShown
    {
        $groups = [];

        foreach ($changes as $group) {
            $entries = [];

            foreach ($group as $entry) {
                $entries[] = $entry;
            }

            $groups[] = new AGroupOfTheNotesAsShown($group->title(), $entries);
        }

        return new TheReleaseNotesAsShown(
            $release->version(),
            $release->theHouseholdWouldNotice() ? 'updates.would_be_noticed' : 'updates.would_not_be_noticed',
            $release->wasWithdrawn(),
            $groups,
        );
    }

    /**
     * Why the notes are not shown.
     *
     * Notes the stack calls current but names no release for are notes not
     * written for this copy, and said as that.
     */
    private function withheld(HowTheNotesStand $notes): WhatWithheldNotesSay
    {
        return new HowWithheldNotesRead()->notWrittenForThisCopy($notes);
    }
}
