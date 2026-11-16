<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Playing;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\AChannelToPlay;
use Modules\Kernel\Api\ALiveStream;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Playing;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Tuning;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhyPlayingDidNotStart;

/**
 * Pressing a channel: the core opens its live stream for the member, and the player is handed it.
 *
 * The core decides, as the channel is pressed, whether this member may watch
 * it and what is on it now; nothing drawn earlier stands in for that answer.
 * A channel on the player ends whatever title was on it, and a title put on
 * it after ends the channel, so one keeper hears each move.
 */
final readonly class PutsAChannelOnScreen
{
    use LetsGoOfARefusedSession;

    public function __construct(
        private SecureStorage $storage,
        private Tuning $tuning,
        private Playing $player,
        private TheChannelOnScreen $channel,
        private WhatIsPlaying $titles,
    ) {}

    /** Open a channel as whoever is signed in to the stack, under the name the lock screen calls it. */
    public function tune(Stack $stack, ChannelId $channel, string $named): WhatPressingAChannelCameTo
    {
        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session, Whose $whose): WhatPressingAChannelCameTo => $this->tuning->tune($stack, $session, $whose, $channel)->either(
                tuned: fn(ALiveStream $stream): WhatPressingAChannelCameTo => $this->open(AChannelOnScreen::of($stack, $whose, $channel, $stream), $session, $named),
                cannot: static fn(Sentence $why): WhatPressingAChannelCameTo => WhatPressingAChannelCameTo::cannot($why),
                refused: $this->lettingGoIfRefused($stack, static fn(Obstacle $why): WhatPressingAChannelCameTo => WhatPressingAChannelCameTo::met($why)),
            ),
            notHeld: static fn(): WhatPressingAChannelCameTo => WhatPressingAChannelCameTo::signedOut(),
        );
    }

    /** Hand the player the stream; where it will not take it, the core is told the stream stopped rather than left to lapse. */
    private function open(AChannelOnScreen $on, Session $session, string $named): WhatPressingAChannelCameTo
    {
        return $this->player->tune(AChannelToPlay::of($on->stream(), $named))->either(
            opened: function () use ($on, $session): WhatPressingAChannelCameTo {
                $this->letGoOfThePrevious($on, $session);
                $this->titles->over();
                $this->channel->began($on);

                return WhatPressingAChannelCameTo::onScreen();
            },
            refused: function (WhyPlayingDidNotStart $why) use ($on, $session): WhatPressingAChannelCameTo {
                $this->tuning->stop($on->stack(), $session, $on->whose(), $on->stream());

                return WhatPressingAChannelCameTo::notStarted($why);
            },
        );
    }

    /** A channel the new one replaces on the player, on the same stack, is told to the core as stopped rather than left to lapse. */
    private function letGoOfThePrevious(AChannelOnScreen $on, Session $session): void
    {
        $previous = $this->channel->now();

        if ($previous instanceof AChannelOnScreen && $previous->stack()->id()->is($on->stack()->id())) {
            $this->tuning->stop($previous->stack(), $session, $previous->whose(), $previous->stream());
        }
    }
}
