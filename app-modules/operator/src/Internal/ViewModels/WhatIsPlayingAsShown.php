<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

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
     * @param HowTheReadingWent            $went       whether asking came back, or what stood in the way, a session that ended among it
     * @param bool                         $isTheOperator whether the session is the operator's, the only one this is shown to
     * @param bool                         $heard      whether the core or the stream has said anything yet
     * @param list<ASessionPlayingAsShown> $sessions   every session playing, in the core's order
     * @param string                       $unread     what the core said instead, where it could not read the media server, or empty
     * @param AgoAsShown                   $ago        when it was heard, or live where it is current
     * @param bool                         $broke      whether the stream broke or could not be opened
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $isTheOperator,
        public bool $heard,
        public array $sessions,
        public string $unread,
        public AgoAsShown $ago,
        public bool $broke,
    ) {}
}
