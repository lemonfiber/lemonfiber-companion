<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * Home's Live TV shelf: the channels the member may watch, and what came of the last one pressed.
 *
 * No channels draws no shelf, whether the house has no live TV, this
 * member's account may not watch it, or the list could not be had: Home's
 * own reading says what stood in the way of reaching the house.
 */
final readonly class WhatTheChannelsTurnedOutToBe
{
    /** @param list<WhatOneChannelSays> $channels */
    private function __construct(public array $channels, public ?WhatAChannelCameTo $came) {}

    /** @param list<WhatOneChannelSays> $channels */
    public static function these(array $channels): self
    {
        return new self($channels, null);
    }

    public static function none(): self
    {
        return new self([], null);
    }

    /** The same shelf, saying what came of pressing one of its channels. */
    public function after(WhatAChannelCameTo $came): self
    {
        return new self($this->channels, $came->isNothing() ? null : $came);
    }

    /** Whether the shelf is drawn at all. */
    public function isDrawn(): bool
    {
        return $this->channels !== [];
    }

    /** The name a channel on the shelf is drawn with, or its id where the shelf does not hold it. */
    public function nameOf(string $id): string
    {
        foreach ($this->channels as $channel) {
            if ($channel->id === $id) {
                return $channel->name;
            }
        }

        return $id;
    }
}
