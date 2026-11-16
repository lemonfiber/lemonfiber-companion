<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/** What was last heard of what is playing, and when. */
final readonly class WhatWasHeardPlaying
{
    /**
     * @param list<ASessionPlayingAsShown> $sessions every session playing, in the core's order
     * @param bool                         $read     whether the core could read the media server
     * @param list<AnObstacleAsShown>      $unread   what stood in the way, where it could not, in the core's order
     * @param AgoAsShown                   $ago      when it was heard, or live where it is current
     */
    public function __construct(public array $sessions, public bool $read, public array $unread, public AgoAsShown $ago) {}
}
