<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Unattended;
use Modules\Operator\Internal\ViewModels\WhatOneUnattendedCommandSays;

/**
 * One long-running command, as the row a screen lists it on.
 *
 * `F2` — the command is the only argument, so a row is read in a test by
 * stating one command and nothing else.
 */
final readonly class HowAnUnattendedCommandReads
{
    /**
     * Fold one command into the fields a row needs.
     *
     * `missing()` is answered here rather than in the screen, so a screen
     * listing what a machine keeps running is a loop over this and not a fold
     * per row.
     *
     * The empty string is the *absent* arm rather than a default, and the
     * difference matters: the value one layer down refuses a blank program, so
     * an empty here can only have come from the arm that says nothing is
     * missing. A template branching on it is therefore asking the question the
     * two arms answer, rather than guessing from a field that might have
     * arrived blank.
     */
    public function in(Unattended $command): WhatOneUnattendedCommandSays
    {
        $standing = $command->standing();

        return $command->missing(
            gone: static fn(string $where): WhatOneUnattendedCommandSays => new WhatOneUnattendedCommandSays(
                name: $command->name(),
                command: $command->command(),
                guarantees: $command->guarantees(),
                standingSaid: $standing->saidOnTheScreen(),
                didNotComeBack: $standing->didNotComeBack(),
                missing: $where,
            ),
            nothing: static fn(): WhatOneUnattendedCommandSays => new WhatOneUnattendedCommandSays(
                name: $command->name(),
                command: $command->command(),
                guarantees: $command->guarantees(),
                standingSaid: $standing->saidOnTheScreen(),
                didNotComeBack: $standing->didNotComeBack(),
                missing: '',
            ),
        );
    }
}
