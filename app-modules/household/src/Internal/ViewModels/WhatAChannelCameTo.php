<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * What came of pressing a channel, said beside it on the shelf: nothing where it plays.
 *
 * Three ways to say it. The core's own words, where it locates nothing, as
 * away from home; what stood in the way, where asking met an obstacle or the
 * pass lapsed unrenewed; or one of this app's lines, where the player would
 * not open or stopped.
 */
final readonly class WhatAChannelCameTo
{
    private function __construct(
        public string $channel,
        public string $inTheHousesWords,
        public string $key,
        public ?WhatStoppedAMember $stopped,
    ) {}

    public static function nothing(): self
    {
        return new self('', '', '', null);
    }

    /** The core said why, in its own words. */
    public static function said(string $channel, string $sentence): self
    {
        return new self($channel, $sentence, '', null);
    }

    /** Something stood in the way, and this is what the member met. */
    public static function stopped(string $channel, WhatStoppedAMember $stopped): self
    {
        return new self($channel, '', '', $stopped);
    }

    /** One of this app's lines, as a catalogue key; nothing where the key is empty. */
    public static function inOurWords(string $channel, string $key): self
    {
        return $key === '' ? self::nothing() : new self($channel, '', $key, null);
    }

    public function isNothing(): bool
    {
        return $this->inTheHousesWords === '' && $this->key === '' && ! $this->stopped instanceof WhatStoppedAMember;
    }
}
