<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\ALiveStream;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheChannels;
use Modules\Kernel\Api\TheGuide;
use Modules\Kernel\Api\Tuning;
use Modules\Kernel\Api\WhatIsOnTheChannels;
use Modules\Kernel\Api\WhatRenewingCameTo;
use Modules\Kernel\Api\WhatStoppingCameTo;
use Modules\Kernel\Api\WhatTheGuideSays;
use Modules\Kernel\Api\WhatTuningCameTo;
use Modules\Kernel\Api\Whose;

/**
 * Live TV where a test says what the core answers, and which remembers what it was asked.
 *
 * Not `readonly`: what was asked is written when the asking happens. The
 * operator is answered as not theirs to ask, as the adapter answers them.
 */
final class ATunerThatAnswered implements Tuning
{
    /** How many times the channels were asked, which is how a screen asking twice a frame is caught. */
    private int $askings = 0;

    /** How many times the guide was asked, for the same reason. */
    private int $guideAskings = 0;

    /** @var list<string> each channel tuned, by its id, in order */
    private array $tuned = [];

    /** @var list<string> each stream renewed, by its id, in order */
    private array $renewed = [];

    /** @var list<string> each stream stopped, by its id, in order */
    private array $stopped = [];

    private function __construct(
        private readonly WhatIsOnTheChannels $channels,
        private WhatTuningCameTo $tuning,
        private WhatRenewingCameTo $renewing,
        private WhatStoppingCameTo $stopping,
        private WhatTheGuideSays $guide,
    ) {}

    /** A house whose core lists these channels, and opens, renews and closes what is asked. */
    public static function listing(TheChannels $channels): self
    {
        return new self(
            WhatIsOnTheChannels::told($channels),
            WhatTuningCameTo::refused(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
            WhatRenewingCameTo::refused(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
            WhatStoppingCameTo::stopped(),
            WhatTheGuideSays::told(TheGuide::of()),
        );
    }

    /** A stack that would not list its channels, and what stood in the way. */
    public static function met(Obstacle $why): self
    {
        return new self(
            WhatIsOnTheChannels::refused($why),
            WhatTuningCameTo::refused($why),
            WhatRenewingCameTo::refused($why),
            WhatStoppingCameTo::refused($why),
            WhatTheGuideSays::refused($why),
        );
    }

    /** The same tuner, answering the guide with this. */
    public function guiding(TheGuide $guide): self
    {
        $this->guide = WhatTheGuideSays::told($guide);

        return $this;
    }

    /** The same tuner, answering a channel opened with this. */
    public function tuning(WhatTuningCameTo $answer): self
    {
        $this->tuning = $answer;

        return $this;
    }

    /** The same tuner, answering a renewal with this. */
    public function renewing(WhatRenewingCameTo $answer): self
    {
        $this->renewing = $answer;

        return $this;
    }

    /** The same tuner, answering a stop with this. */
    public function stopping(WhatStoppingCameTo $answer): self
    {
        $this->stopping = $answer;

        return $this;
    }

    public function askings(): int
    {
        return $this->askings;
    }

    public function guideAskings(): int
    {
        return $this->guideAskings;
    }

    /** @return list<string> */
    public function tuned(): array
    {
        return $this->tuned;
    }

    /** @return list<string> */
    public function renewed(): array
    {
        return $this->renewed;
    }

    /** @return list<string> */
    public function stopped(): array
    {
        return $this->stopped;
    }

    public function theChannelsOf(Stack $stack, Session $session, Whose $whose): WhatIsOnTheChannels
    {
        $this->askings++;

        return $whose->either(
            operator: static fn(): WhatIsOnTheChannels => WhatIsOnTheChannels::refused(Obstacle::of(KindOfObstacle::NotForThisAccount)),
            member: fn(): WhatIsOnTheChannels => $this->channels,
        );
    }

    public function theGuideOf(Stack $stack, Session $session, Whose $whose): WhatTheGuideSays
    {
        $this->guideAskings++;

        return $whose->either(
            operator: static fn(): WhatTheGuideSays => WhatTheGuideSays::refused(Obstacle::of(KindOfObstacle::NotForThisAccount)),
            member: fn(): WhatTheGuideSays => $this->guide,
        );
    }

    public function tune(Stack $stack, Session $session, Whose $whose, ChannelId $channel): WhatTuningCameTo
    {
        return $whose->either(
            operator: static fn(): WhatTuningCameTo => WhatTuningCameTo::refused(Obstacle::of(KindOfObstacle::NotForThisAccount)),
            member: function () use ($channel): WhatTuningCameTo {
                $this->tuned[] = $channel->named();

                return $this->tuning;
            },
        );
    }

    public function renew(Stack $stack, Session $session, Whose $whose, ALiveStream $stream): WhatRenewingCameTo
    {
        return $whose->either(
            operator: static fn(): WhatRenewingCameTo => WhatRenewingCameTo::refused(Obstacle::of(KindOfObstacle::NotForThisAccount)),
            member: function () use ($stream): WhatRenewingCameTo {
                $this->renewed[] = $stream->stream();

                return $this->renewing;
            },
        );
    }

    public function stop(Stack $stack, Session $session, Whose $whose, ALiveStream $stream): WhatStoppingCameTo
    {
        return $whose->either(
            operator: static fn(): WhatStoppingCameTo => WhatStoppingCameTo::refused(Obstacle::of(KindOfObstacle::NotForThisAccount)),
            member: function () use ($stream): WhatStoppingCameTo {
                $this->stopped[] = $stream->stream();

                return $this->stopping;
            },
        );
    }
}
