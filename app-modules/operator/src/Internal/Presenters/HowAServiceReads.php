<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function array_filter;
use function array_values;

use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\WhatOneServiceSays;

/**
 * What one service a stack runs comes to, as the fields a row reads.
 *
 * **What leans on it travels with the row.** A disruptive action has to
 * state what it disturbs, and what a stop disturbs is not knowable from the
 * service alone — it is the other services that will not work without it. A
 * screen that had to go back and ask would be a screen that could forget to.
 */
final readonly class HowAServiceReads
{
    /**
     * Fold one service into the fields a row needs.
     *
     * The exit code is folded here rather than in the screen, for
     * {@see HowAStalledItemReads::in()}'s reason: one closure builds the whole
     * row, so a field cannot reach a template by a path that skipped the value
     * object.
     */
    public function in(Daemon $daemon): WhatOneServiceSays
    {
        $leaning = [];

        foreach ($daemon->whatLeansOnIt() as $id) {
            $leaning[] = $id->named();
        }

        return new WhatOneServiceSays(
            id: $daemon->id(),
            name: $daemon->name(),
            profile: $daemon->profile()->named(),
            runsSaid: $daemon->runs()->saidOnTheScreen(),
            mattersSaid: $daemon->matters()->saidOnTheScreen(),
            isSettling: $daemon->runs()->isSettling(),
            isOurs: $daemon->runs()->isThisStacksToRun(),
            wouldNotHelp: $daemon->runs()->isAlreadyBeingRestarted(),
            leaning: $leaning,
            exited: $this->exited($daemon),
            // Asked of the state rather than worked out here, so one screen
            // cannot come to a different answer from another about what a
            // stopped service can be told to do. A walk over the three rather
            // than a list handed back, because `D1` refuses an array crossing a
            // module boundary — the decision is still the enum's and this is
            // only the shape it arrives in.
            verbs: array_values(array_filter(
                WhatToDoWithIt::cases(),
                static fn(WhatToDoWithIt $verb): bool => $daemon->runs()->mayTake($verb),
            )),
        );
    }

    /**
     * What it exited with, as text, or nothing where it did not.
     *
     * The empty string rather than a zero, because a service that is running
     * has no exit code at all and `0` is the code for one that ended well — the
     * two must not render the same.
     */
    private function exited(Daemon $daemon): string
    {
        return $daemon->exit(
            said: static fn(int $code): AsText => AsText::of((string) $code),
            unstated: static fn(): AsText => AsText::nothing(),
        )->said;
    }
}
