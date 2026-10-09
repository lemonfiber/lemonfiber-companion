<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of putting the player on screen: it is there, or why it is not.
 */
final readonly class WhatOpeningCameTo
{
    private function __construct(private ?WhyPlayingDidNotStart $why) {}

    /** The player is on screen. */
    public static function opened(): self
    {
        return new self(null);
    }

    /** It is not, and this is why. */
    public static function refused(WhyPlayingDidNotStart $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in both cases, and get back what you built.
     *
     * @template TOpened of object
     * @template TRefused of object
     *
     * @param  Closure(): TOpened  $opened
     * @param  Closure(WhyPlayingDidNotStart): TRefused  $refused
     * @return TOpened|TRefused
     */
    public function either(Closure $opened, Closure $refused): object
    {
        return $this->why instanceof WhyPlayingDidNotStart ? $refused($this->why) : $opened();
    }
}
