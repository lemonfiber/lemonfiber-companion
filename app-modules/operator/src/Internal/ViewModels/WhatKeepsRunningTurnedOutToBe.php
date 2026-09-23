<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a machine what it keeps running produced, flattened for a template.
 *
 * The sibling of {@see WhatStoppedTurnedOutToBe} and written the same way:
 * {@see \Modules\Operator\Internal\Presenters\HowHostingReads} folds the answer
 * once and the template reads fields, because Blade has no `either()` and
 * cannot be given one.
 *
 * **Four states, and two of them look like nothing being wrong.** A machine
 * that keeps everything running is the answer an operator wants; a machine this
 * product cannot configure is a fact about the platform; a session that has
 * ended is the sign-in screen; an obstacle is its own. The second is the one
 * that must not fold into the first — *not available here* drawn as an empty
 * list reads as *this machine hosts nothing*, which is the reading the whole
 * surface exists to refuse.
 *
 * **`$instead` carries that distinction and is empty everywhere else.** A
 * template printing it unconditionally would put a blank under a heading on
 * every machine that has a manager, so the field is what the template branches
 * on — the shape {@see WhatStoppedTurnedOutToBe} uses for `$shownSaid`.
 */
final readonly class WhatKeepsRunningTurnedOutToBe
{
    /**
     * @param list<WhatOneUnattendedCommandSays> $commands  every command, in the machine's order
     * @param string $keptBySaid the key for what keeps them running
     * @param string $instead    what to do where this product configures nothing, or empty
     * @param int    $missing    how many are installed and not running
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $commands,
        public string $keptBySaid,
        public string $instead,
        public int $missing,
    ) {}
}
