<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Modules\Household\Internal\TheLiveTvShelf;
use Modules\Household\Internal\ViewModels\WhatTheChannelsTurnedOutToBe;
use Modules\Kernel\Api\Stack;

/**
 * Home's Live TV shelf, a channel pressed on it, and the line beside it where it did not play or stopped.
 *
 * Plays titles too, and hears the player for both: a title stopped is said
 * beside its Play, and a channel stopped beside the shelf, the way back to
 * the channels being the shelf it is said on.
 *
 * @phpstan-require-implements HearsThePlayer
 */
trait PlaysChannels
{
    use PlaysTitles {
        playerMoved as private titlePlayerMoved;
    }

    /** The channels, once the frame has asked; public so the screen's state holds it. */
    public ?WhatTheChannelsTurnedOutToBe $live = null;

    /** The channel last pressed, by its id, so a stop can be told against it; public for the same reason. */
    public string $tunedTo = '';

    /** The channels the member may watch, asked once per frame. */
    public function channels(): WhatTheChannelsTurnedOutToBe
    {
        return $this->live ??= $this->liveTv()->of($this->stack());
    }

    /** Press a channel on the shelf, by the id the core lists it under. */
    public function tuneTo(string $channel): void
    {
        if ($channel === '') {
            return;
        }

        $shelf = $this->channels();
        $this->tunedTo = $channel;
        $this->live = $shelf->after($this->liveTv()->tune($this->stack(), $channel, $shelf->nameOf($channel)));
    }

    /** The player moved: a title or the channel last pressed here that stopped and cannot go on says why. */
    public function playerMoved(): void
    {
        $this->titlePlayerMoved();

        if ($this->tunedTo === '') {
            return;
        }

        $shelf = $this->channels();
        $came = $this->liveTv()->howItStopped($this->tunedTo, $shelf->nameOf($this->tunedTo));

        if (! $came->isNothing()) {
            $this->live = $shelf->after($came);
        }
    }

    abstract public function stack(): Stack;

    abstract protected function liveTv(): TheLiveTvShelf;
}
