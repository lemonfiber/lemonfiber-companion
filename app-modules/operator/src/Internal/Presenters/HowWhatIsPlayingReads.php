<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Health\Api\WhatAStreamSaidSoFar;
use Modules\Kernel\Api\ASessionPlaying;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatTheHouseholdIsPlaying;
use Modules\Operator\Internal\ViewModels\ASessionPlayingAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatIsPlayingAsShown;
use Modules\Operator\Internal\ViewModels\WhatTheOperatorHearsPlaying;
use Modules\Operator\Internal\ViewModels\WhatWasHeardPlaying;

/** Turns what the household is playing, as heard so far, into what the screen draws. */
final readonly class HowWhatIsPlayingReads
{
    public const string ALONE = 'stacks.playing.alone';

    public const string IN_SERIES = 'stacks.playing.in_series';

    public const string NUMBERED = 'stacks.playing.numbered';

    public const string PAUSED = 'stacks.playing.paused';

    public const string PLAYING = 'stacks.playing.playing';

    /** @param WhatAStreamSaidSoFar<WhatTheHouseholdIsPlaying> $heard */
    public function of(WhatAStreamSaidSoFar $heard, Instant $now): WhatIsPlayingAsShown
    {
        $broke = $heard->hasBroken();

        return $heard->heard(
            none: static fn(): WhatIsPlayingAsShown => new WhatIsPlayingAsShown(HowTheReadingWent::itCameBack(), new WhatTheOperatorHearsPlaying($broke, null)),
            current: fn(WhatTheHouseholdIsPlaying $playing): WhatIsPlayingAsShown => $this->playing($playing, AgoAsShown::live(), $broke),
            asOf: fn(WhatTheHouseholdIsPlaying $playing, Instant $at): WhatIsPlayingAsShown
                => $this->playing($playing, AgoAsShown::from(HowLongAgo::since($at, $now), $at, $now), $broke),
        );
    }

    public function signedOut(): WhatIsPlayingAsShown
    {
        return new WhatIsPlayingAsShown(HowTheReadingWent::theSessionEnded(), null);
    }

    public function met(Obstacle $why): WhatIsPlayingAsShown
    {
        return new WhatIsPlayingAsShown(HowTheReadingWent::somethingStopped($why), null);
    }

    public function forAMember(): WhatIsPlayingAsShown
    {
        return new WhatIsPlayingAsShown(HowTheReadingWent::itCameBack(), null);
    }

    private function playing(WhatTheHouseholdIsPlaying $playing, AgoAsShown $ago, bool $broke): WhatIsPlayingAsShown
    {
        $sessions = [];

        foreach ($playing as $session) {
            $sessions[] = $this->session($session);
        }

        return new WhatIsPlayingAsShown(HowTheReadingWent::itCameBack(), new WhatTheOperatorHearsPlaying($broke, new WhatWasHeardPlaying($sessions, $playing->whyNot(), $ago)));
    }

    private function session(ASessionPlaying $session): ASessionPlayingAsShown
    {
        return $session->what(
            alone: fn(string $title): ASessionPlayingAsShown => $this->card($session, self::ALONE, $title),
            inSeries: fn(string $series, string $title): ASessionPlayingAsShown => $this->card($session, self::IN_SERIES, $title, ['series' => $series]),
            numbered: fn(string $series, int $season, int $episode, string $title): ASessionPlayingAsShown
                => $this->card($session, self::NUMBERED, $title, ['series' => $series, 'season' => (string) $season, 'episode' => (string) $episode]),
        );
    }

    /** @param array<string, string> $around what fills the line beside the title */
    private function card(ASessionPlaying $session, string $what, string $title, array $around = []): ASessionPlayingAsShown
    {
        return new ASessionPlayingAsShown(
            member: $session->member(),
            what: $what,
            filling: ['title' => $title, ...$around],
            device: $session->device(),
            state: $session->isPaused() ? self::PAUSED : self::PLAYING,
        );
    }
}
