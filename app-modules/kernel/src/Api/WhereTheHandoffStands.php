<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where handing one person's device over stands, as the stack says it.
 *
 * *Waiting for them* is told apart from *did not work*: until the person takes
 * the step on their device there is nothing to prove, and nothing has gone
 * wrong.
 */
enum WhereTheHandoffStands: string
{
    /** Nobody by that name holds an account, so there is nothing to sign in to. */
    case Unprovisioned = 'unprovisioned';

    /** The code was given by this asking. */
    case Ready = 'ready';

    /** The code went out earlier and no device of theirs has signed in since. */
    case Pending = 'pending';

    /** A device that was not signed in when the code was given is signed in now. */
    case Connected = 'connected';

    /** It could not go ahead, for the reason given beside it. */
    case Failed = 'failed';

    /** Whether there is a code to hand over while it stands here. */
    public function handsACodeOver(): bool
    {
        return match ($this) {
            self::Ready, self::Pending => true,
            self::Unprovisioned, self::Connected, self::Failed => false,
        };
    }
}
