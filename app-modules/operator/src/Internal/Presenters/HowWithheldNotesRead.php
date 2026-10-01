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
    public function of(HowTheNotesStand $notes): WhatWithheldNotesSay
    {
        return match ($notes) {
            HowTheNotesStand::Current => new WhatWithheldNotesSay('', ''),
            HowTheNotesStand::Pending => new WhatWithheldNotesSay('stacks.versions.notes_pending', 'stacks.versions.notes_pending_means'),
            HowTheNotesStand::Stale => new WhatWithheldNotesSay('stacks.versions.notes_stale', 'stacks.versions.notes_stale_means'),
        };
    }
}
