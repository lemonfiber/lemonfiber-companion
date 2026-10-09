<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where the player stands, and how far into the title it is.
 *
 * Read by asking the device, never carried by what told the app to ask: the
 * device's word that the player moved carries nothing, so a forged one can
 * only make the app look again.
 */
final readonly class WherePlayingStands
{
    private function __construct(private PlaybackIs $is, private HowFarIn $howFarIn) {}

    public static function of(PlaybackIs $is, HowFarIn $howFarIn): self
    {
        return new self($is, $howFarIn);
    }

    public function is(): PlaybackIs
    {
        return $this->is;
    }

    public function howFarIn(): HowFarIn
    {
        return $this->howFarIn;
    }
}
