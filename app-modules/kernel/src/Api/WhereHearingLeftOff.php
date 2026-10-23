<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The last event each stack's stream carried to this phone, kept so a stream opened later resumes after it.
 *
 * A stream opened naming the last event this phone saw is handed what the
 * stack raised since, where the stack still holds all of it, so the alerts
 * raised while nobody here was listening are told when the phone listens
 * again: after another screen, after the app was away, and after a launch.
 *
 * **What it hands back may not be everything.** A stack that no longer holds
 * the whole of what came after hands back nothing, and does not say so; the
 * operator is told that some alerts may be missing where notifications are
 * turned on.
 *
 * **A marker the phone keeps about a stack**, so clearing what the phone keeps
 * clears it, and removing the stack from the phone lets go of its part.
 */
interface WhereHearingLeftOff extends ForgetsAStack, ForgetsEverythingKept
{
    /** Keep the last event this stack's stream carried, in place of the one before, and say whether it was kept. */
    public function heardTo(StackId $stack, AnEventHeard $event): bool;

    /** Where a stream opened for this stack starts: after the event kept for it, or from the start where none is. */
    public function leftOffAt(StackId $stack): WhereAStreamResumes;
}
