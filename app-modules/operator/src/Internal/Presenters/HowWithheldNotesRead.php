<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Operator\Internal\ViewModels\WhatWithheldNotesSay;

/**
 * What a screen says where it does not show the running release's notes.
 *
 * One place for it, because two screens draw the same notes: the versions
 * screen and the update screen. Notes the record has not written yet, and notes
 * out of step with the build, are each said as what they are, and current notes
 * withhold nothing.
 */
final readonly class HowWithheldNotesRead
{
    /** Why the notes are not shown, or null where they are current. */
    public function of(HowTheNotesStand $notes): ?WhatWithheldNotesSay
    {
        return $notes === HowTheNotesStand::Current ? null : $this->notWrittenForThisCopy($notes);
    }

    /** Why notes not written for this copy are not shown: stale where the stack says so, and still to come otherwise. */
    public function notWrittenForThisCopy(HowTheNotesStand $notes): WhatWithheldNotesSay
    {
        return $notes === HowTheNotesStand::Stale
            ? new WhatWithheldNotesSay('stacks.versions.notes_stale', 'stacks.versions.notes_stale_means')
            : new WhatWithheldNotesSay('stacks.versions.notes_pending', 'stacks.versions.notes_pending_means');
    }
}
