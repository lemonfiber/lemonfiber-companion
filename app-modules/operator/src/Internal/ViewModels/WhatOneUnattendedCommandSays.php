<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One long-running command, flattened for a template to read.
 *
 * {@see \Modules\Kernel\Api\Unattended} hands the program that is gone over
 * through a closure and Blade has no way to call one, so
 * {@see \Modules\Operator\Internal\Presenters\HowAnUnattendedCommandReads}
 * folds it once per row into this — the argument {@see WhatOneStalledItemSays}
 * makes, and the reason that class exists.
 *
 * **The first three are always set**, because the value they come from refuses
 * to be built without them. A row that reached a template with a name and no
 * command would ask somebody to decide whether a blank should survive every
 * reboot, and the type one layer down exists to make that unspellable; this
 * class must not undo it by defaulting a field to the empty string.
 *
 * **The standing arrives as a key rather than as a word**, for
 * {@see WhatOneStalledItemSays}'s reason: the word is the catalogue's, so a
 * standing added to the contract cannot arrive here with a sentence written in
 * this file that no translator can reach (`L1`).
 *
 * **`$missing` is the one field that is empty on most rows, and the template
 * must branch on it rather than print it.** Only an orphan has a program that
 * is gone; a blank printed where a path belongs reads as *nothing is missing*
 * on a row where something is, which is what the two arms one layer down exist
 * to prevent.
 */
final readonly class WhatOneUnattendedCommandSays
{
    /**
     * @param string $name           what this product calls it, which is what an operator reads
     * @param string $command        how it is typed in a terminal
     * @param string $guarantees     what it does for as long as it runs
     * @param string $standingSaid   the key for what stands between it and the machine
     * @param bool   $didNotComeBack whether it is installed and is not running
     * @param string $missing        the program that is gone, or empty where nothing is
     */
    public function __construct(
        public string $name,
        public string $command,
        public string $guarantees,
        public string $standingSaid,
        public bool $didNotComeBack,
        public string $missing,
    ) {}
}
