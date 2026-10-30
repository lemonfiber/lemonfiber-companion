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
    /** Why a series with no episode to play has nothing to press. */
    private const string NOTHING_TO_PLAY = 'household.title.nothing_to_play';

    /** @param string|ALineAsShown|null $waits why Play waits: the core's words, this app's line, or null where Play plays */
    private function __construct(public string|ALineAsShown|null $waits) {}

    /** The core says where it plays, so Play plays it. */
    public static function plays(): self
    {
        return new self(null);
    }

    /** The core states no location for it, and this is why, in its words. */
    public static function cannot(string $coresWords): self
    {
        return new self($coresWords);
    }

    /** Nothing in it streams. */
    public static function nothingPlays(): self
    {
        return new self(new ALineAsShown(self::NOTHING_TO_PLAY, []));
    }

    /** Whether Play can be pressed. */
    public function canPlay(): bool
    {
        return $this->waits === null;
    }
}
