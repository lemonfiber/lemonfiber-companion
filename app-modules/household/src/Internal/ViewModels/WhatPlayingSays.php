<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * Play, as a title's screen draws it: whether it can be pressed, and the reason beside it where it cannot.
 *
 * The reason is the core's own words where the core said why no location is
 * stated, drawn as written; otherwise it is this app's catalogue key.
 */
final readonly class WhatPlayingSays
{
    /** Why a title that streams cannot be played here. */
    private const string WAITS = 'household.title.cannot_play';

    /** Why a series with no episode to play has nothing to press. */
    private const string NOTHING_TO_PLAY = 'household.title.nothing_to_play';

    private function __construct(public bool $canPlay, public string $why, public bool $isInTheCoresWords) {}

    /** It streams, and this app does not play it yet. */
    public static function waits(): self
    {
        return new self(canPlay: false, why: self::WAITS, isInTheCoresWords: false);
    }

    /** The core states no location for it, and this is why, in its words. */
    public static function cannot(string $coresWords): self
    {
        return new self(canPlay: false, why: $coresWords, isInTheCoresWords: true);
    }

    /** Nothing in it streams. */
    public static function nothingPlays(): self
    {
        return new self(canPlay: false, why: self::NOTHING_TO_PLAY, isInTheCoresWords: false);
    }
}
