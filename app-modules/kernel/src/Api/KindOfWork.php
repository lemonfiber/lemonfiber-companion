<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Which kind of work a handle was left running for.
 *
 * {@see WorkLeftRunning} keeps a handle per stack *and* per kind, because a
 * stack takes on more than one kind of work and each is followed from a screen
 * of its own. A handle kept for one kind is never asked after as another: the
 * stack would answer about work that screen did not start, in a shape it does
 * not draw.
 *
 * The value is part of the key a handle is kept under on the device, so
 * renaming a case orphans every handle kept under the old name.
 */
enum KindOfWork: string
{
    /** Fetching one thing, narrated end to end. */
    case Walkthrough = 'walkthrough';
}
