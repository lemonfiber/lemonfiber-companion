<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Whether the release notes a copy of lemonfiber carries describe that copy, as the stack said it.
 *
 * The `changelog` block's own `state`. Three cases because the wire has three,
 * and the last two are told apart because they mean different things: notes not
 * written yet is a lag after a release, and notes out of step is a record that
 * contradicts the build, so neither is ever read as current.
 *
 * **Not the update envelope's top-level `state`**, which is {@see AgainstThePins}
 * and says whether any service would move.
 */
enum HowTheNotesStand: string
{
    /** The notes hold the running release and claim nothing later. */
    case Current = 'current';
    /** The running release has no notes yet. */
    case Pending = 'pending';
    /** The notes and what this build could have shipped disagree. */
    case Stale = 'stale';
}
