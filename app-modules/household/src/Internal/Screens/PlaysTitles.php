<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Modules\Household\Internal\Playing\PutsATitleOnScreen;
use Modules\Household\Internal\Playing\WhatPressingPlayCameTo;
use Modules\Household\Internal\Presenters\HowPlayingIsTold;
use Modules\Household\Internal\Presenters\InWords;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhyPlayingDidNotStart;

/**
 * Play, pressed on a household screen, and the line beside it where it did not play or stopped.
 *
 * A screen using this says what to do where the core's answer changed under
 * it, and where something stood in the way of asking.
 *
 * @phpstan-require-implements HearsThePlayer
 */
trait PlaysTitles
{
    /** Why the last Play did not play or stopped, as a catalogue key, or empty; public so the screen's state holds it. */
    public string $playingSaid = '';

    /** The title the last Play was pressed for, so a stop can be told against it; public for the same reason. */
    public string $pressedFor = '';

    /** The player moved: where the title last played from here stopped and cannot go on, say why. */
    public function playerMoved(): void
    {
        if ($this->pressedFor === '') {
            return;
        }

        $said = HowPlayingIsTold::stopped($this->titlesOnScreen()->howItStoppedOn(HoldingId::called($this->pressedFor)));

        if ($said !== '') {
            $this->playingSaid = $said;
        }
    }

    abstract protected function titlesOnScreen(): PutsATitleOnScreen;

    /** The core's answer, asked again, plays nothing now: draw what it says instead. */
    abstract protected function playsNothingNow(HoldingId $title): void;

    /** Asking the core met this; a session it refused is already let go of. */
    abstract protected function metOnPlay(Obstacle $why): void;

    /** What pressing Play for a title came to, kept as the line beside it. */
    protected function pressed(HoldingId $title, WhatPressingPlayCameTo $came): void
    {
        $this->pressedFor = $title->named();

        $this->playingSaid = $came->either(
            onScreen: static fn(): InWords => new InWords(''),
            met: function (Obstacle $why): InWords {
                $this->metOnPlay($why);

                return new InWords('');
            },
            notStarted: static fn(WhyPlayingDidNotStart $why): InWords => new InWords(HowPlayingIsTold::notStarted($why)),
            nothingToPlay: function () use ($title): InWords {
                $this->playsNothingNow($title);

                return new InWords('');
            },
        )->said;
    }
}
