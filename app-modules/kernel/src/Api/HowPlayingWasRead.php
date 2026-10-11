<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of asking the core what the household is playing: its answer, or the reason there is none.
 */
final readonly class HowPlayingWasRead
{
    private function __construct(private WhatTheHouseholdIsPlaying|Obstacle $answer) {}

    /** The core answered. */
    public static function said(WhatTheHouseholdIsPlaying $playing): self
    {
        return new self($playing);
    }

    /** It did not, and this is why. */
    public static function refused(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in both cases, and get back what you built.
     *
     * @template TSaid of object
     * @template TRefused of object
     *
     * @param  Closure(WhatTheHouseholdIsPlaying): TSaid  $said
     * @param  Closure(Obstacle): TRefused  $refused
     * @return TSaid|TRefused
     */
    public function either(Closure $said, Closure $refused): object
    {
        return $this->answer instanceof WhatTheHouseholdIsPlaying ? $said($this->answer) : $refused($this->answer);
    }
}
