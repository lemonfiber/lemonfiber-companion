<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What the household is playing, asked once and then followed on the stack's event stream.
 *
 * {@see HearingTheWalk}'s shape for the stream, for the `playing` event. One of
 * these belongs to one screen.
 */
interface FollowingWhatIsPlaying
{
    /** What the core says the household is playing now. */
    public function now(Stack $stack, Session $session): HowPlayingWasRead;

    /**
     * The last `playing` event since the screen last asked, opening the subscription where it is not open.
     *
     * @return WhatAStreamSaid<WhatTheHouseholdIsPlaying>
     */
    public function heard(Stack $stack, Session $session): WhatAStreamSaid;

    /** @return WhatAStreamSaid<WhatTheHouseholdIsPlaying> */
    public function letGo(): WhatAStreamSaid;
}
