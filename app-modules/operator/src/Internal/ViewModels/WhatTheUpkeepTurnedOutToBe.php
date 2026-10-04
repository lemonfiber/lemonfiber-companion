<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * What asking a stack where it stands produced, flattened for a template.
 *
 * **Three states, and *nothing waiting* is one of them.** A stack that is
 * current is the answer an operator wants; a session that has ended is
 * the sign-in screen; an obstacle is its own. Folding the first two together
 * would have a signed-out phone report a house that is up to date, which is the
 * collapse {@see \Modules\Kernel\Api\WhatIsCurrent} refuses one layer up.
 *
 * **The offer is the update itself, or nothing.** It is built from a reading
 * that said an update is available, so a template cannot offer one the stack
 * did not report — and what the confirmation names is what the offer carries.
 *
 * **A reading the phone kept is drawn whole, and waits.** It came back when it
 * was read, so it is drawn as a reading; what stopped the asking on this
 * frame, where something did, is a fact of its own beside it. Until a fresh
 * reading arrives, every control that would act on the stack is drawn and
 * cannot be used, with how long ago the reading was read beside it.
 */
final readonly class WhatTheUpkeepTurnedOutToBe
{
    /**
     * @param string                         $pinsSaid   the key for where the services stand against their pins
     * @param string                         $running    the version in use, or a dash where the stack named none
     * @param bool                           $runningWasWithdrawn whether the version in use has been taken back
     * @param ?WhatOneReleaseSays            $inUse      what the release in use changed, where the stack named it and its notes are current
     * @param WhatWithheldNotesSay           $notesWithheld why those notes are not shown, where they are not
     * @param list<AnEditAsShown>            $editsKept  the stack files the operator edited, which the update leaves as they set them
     * @param list<WhatOneReleaseSays>       $history    every release the stack's record holds, newest first
     * @param ?TakingAnUpdate                $offer      the update to take, where the stack offered one
     * @param HowTheReadingWent              $askedNow   what this frame's asking met, which stands beside a kept reading where the stack did not answer
     * @param AgoAsShown                     $readAgo    how long ago the reading drawn was read, said only where it was kept
     * @param bool                           $waitsForTheStack whether the reading drawn is one the phone kept, so nothing on it can be acted on yet
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $pinsSaid,
        public string $running,
        public bool $runningWasWithdrawn,
        public ?WhatOneReleaseSays $inUse,
        public WhatWithheldNotesSay $notesWithheld,
        public array $editsKept,
        public array $history,
        public ?TakingAnUpdate $offer,
        public HowTheReadingWent $askedNow,
        public AgoAsShown $readAgo,
        public bool $waitsForTheStack,
    ) {}
}
