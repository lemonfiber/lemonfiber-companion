<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack for the live TV one member may watch, and opening, renewing and closing a channel for them.
 *
 * Which channels a member may watch, and whether what is on one now is
 * within their limits, are the core's to answer, asked again on every open
 * and every renewal, so nothing here filters a channel or keeps one open on
 * its own. Like {@see Watching}, it takes {@see Whose}: a channel is opened
 * on a member's own account, and the operator has none to open it on.
 */
interface Tuning
{
    /** The channels this member may watch, each with what is on it now, or why the core would not say. */
    public function theChannelsOf(Stack $stack, Session $session, Whose $whose): WhatIsOnTheChannels;

    /** Every channel this member may watch, each with its programmes across the window the operator sets, or why the core would not say. */
    public function theGuideOf(Stack $stack, Session $session, Whose $whose): WhatTheGuideSays;

    /** Open one channel's live stream for this member. */
    public function tune(Stack $stack, Session $session, Whose $whose, ChannelId $channel): WhatTuningCameTo;

    /** Renew the passes of a live stream opened for this member, before they lapse. */
    public function renew(Stack $stack, Session $session, Whose $whose, ALiveStream $stream): WhatRenewingCameTo;

    /** Tell the core a live stream opened for this member stopped, so it closes it. */
    public function stop(Stack $stack, Session $session, Whose $whose, ALiveStream $stream): WhatStoppingCameTo;
}
