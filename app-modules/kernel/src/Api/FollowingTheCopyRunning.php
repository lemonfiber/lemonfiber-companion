<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The backup or restore running on a stack, asked once and then followed on the stack's event stream.
 *
 * {@see FollowingWhatIsPlaying}'s shape, for the `copying` event. One of these
 * belongs to one screen.
 */
interface FollowingTheCopyRunning
{
    /** What the core says is being copied now. */
    public function now(Stack $stack, Session $session): HowTheCopyRunningWasRead;

    /**
     * The last `copying` event since the screen last asked, opening the subscription where it is not open.
     *
     * @return WhatAStreamSaid<WhatIsBeingCopied>
     */
    public function heard(Stack $stack, Session $session): WhatAStreamSaid;

    /** @return WhatAStreamSaid<WhatIsBeingCopied> */
    public function letGo(): WhatAStreamSaid;
}
