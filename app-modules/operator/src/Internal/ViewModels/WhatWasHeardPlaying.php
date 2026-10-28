<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/** What was last heard of what is playing, and when. */
final readonly class WhatWasHeardPlaying
{
    /**
     * @param list<ASessionPlayingAsShown> $sessions every session playing, in the core's order
     * @param string                       $unread   what the core said instead, where it could not read the media server, or empty
     * @param AgoAsShown                   $ago      when it was heard, or live where it is current
     */
    public function __construct(public array $sessions, public string $unread, public AgoAsShown $ago) {}
}
