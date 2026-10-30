<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What the household is playing, as the screen draws it, including when nothing can be said.
 *
 * Nothing heard yet, heard and current, or heard before the stream stopped and
 * said with when. Unread is the core saying it could not read the media
 * server, which is not nothing playing.
 */
final readonly class WhatIsPlayingAsShown
{
    /**
     * @param HowTheReadingWent                $went  whether asking came back, or what stood in the way, a session that ended among it
     * @param WhatTheOperatorHearsPlaying|null $hears what the operator has heard, or null where the session is a member's, which is never shown it
     */
    public function __construct(public HowTheReadingWent $went, public ?WhatTheOperatorHearsPlaying $hears) {}
}
