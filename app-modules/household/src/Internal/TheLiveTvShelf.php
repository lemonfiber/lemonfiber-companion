<?php

declare(strict_types=1);

namespace Modules\Household\Internal;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Household\Internal\Playing\PutsAChannelOnScreen;
use Modules\Household\Internal\Playing\TheChannelOnScreen;
use Modules\Household\Internal\Presenters\HowPlayingIsTold;
use Modules\Household\Internal\Presenters\HowTheChannelsRead;
use Modules\Household\Internal\ViewModels\WhatAChannelCameTo;
use Modules\Household\Internal\ViewModels\WhatStoppedAMember;
use Modules\Household\Internal\ViewModels\WhatTheChannelsTurnedOutToBe;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\LocalZone;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheChannels;
use Modules\Kernel\Api\Tuning;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhyPlayingDidNotStart;

/**
 * Home's Live TV shelf: the channels the member may watch, asked as the member, and pressing one.
 *
 * Where the list cannot be had the shelf is not drawn, and a session the
 * stack refused is let go of here, where the refusal is in hand. What came of
 * pressing a channel, or how the last one stopped, is said beside it.
 */
final readonly class TheLiveTvShelf
{
    use LetsGoOfARefusedSession;

    public function __construct(
        private SecureStorage $storage,
        private Tuning $tuning,
        private PutsAChannelOnScreen $screen,
        private TheChannelOnScreen $channel,
        private LocalZone $zone,
    ) {}

    /** The channels the member signed in to the stack may watch. */
    public function of(Stack $stack): WhatTheChannelsTurnedOutToBe
    {
        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session, Whose $whose): WhatTheChannelsTurnedOutToBe => $this->tuning->theChannelsOf($stack, $session, $whose)->either(
                told: fn(TheChannels $channels): WhatTheChannelsTurnedOutToBe => new HowTheChannelsRead($this->zone->zone())->these($channels),
                refused: $this->lettingGoIfRefused($stack, static fn(): WhatTheChannelsTurnedOutToBe => WhatTheChannelsTurnedOutToBe::none()),
            ),
            notHeld: static fn(): WhatTheChannelsTurnedOutToBe => WhatTheChannelsTurnedOutToBe::none(),
        );
    }

    /** Press a channel, drawn under this name; what came of it, said beside it. */
    public function tune(Stack $stack, string $channel, string $named): WhatAChannelCameTo
    {
        return $this->screen->tune($stack, ChannelId::called($channel), $named)->either(
            onScreen: static fn(): WhatAChannelCameTo => WhatAChannelCameTo::nothing(),
            cannot: static fn(Sentence $why): WhatAChannelCameTo => WhatAChannelCameTo::said($named, $why->shown()),
            met: static fn(Obstacle $why): WhatAChannelCameTo => WhatAChannelCameTo::stopped($named, WhatStoppedAMember::by($why)),
            notStarted: static fn(WhyPlayingDidNotStart $why): WhatAChannelCameTo => WhatAChannelCameTo::inOurWords($named, HowPlayingIsTold::notStarted($why)),
            signedOut: static fn(): WhatAChannelCameTo => WhatAChannelCameTo::inOurWords($named, HowPlayingIsTold::SIGNED_OUT),
        );
    }

    /** How the last channel pressed stopped where it could not go on: at its lapse, or on the player; nothing where it did not. */
    public function howItStopped(string $channel, string $named): WhatAChannelCameTo
    {
        $id = ChannelId::called($channel);
        $lapsed = $this->channel->whyItLapsedOn($id);

        return $lapsed instanceof Obstacle
            ? WhatAChannelCameTo::stopped($named, WhatStoppedAMember::by($lapsed))
            : WhatAChannelCameTo::inOurWords($named, HowPlayingIsTold::stopped($this->channel->howItStoppedOn($id)));
    }
}
