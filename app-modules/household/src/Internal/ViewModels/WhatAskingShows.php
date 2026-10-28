<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * What asking for a found title would come to, or came to, as a member is told it.
 *
 * The core's answer where it answered, what stood in the way where something
 * did, and neither where the member's session has ended.
 */
final readonly class WhatAskingShows
{
    private function __construct(
        public ?TheAskAnswered $answer,
        public ?WhatStoppedAMember $stopped,
    ) {}

    /** What the core said asking would come to, or what it came to under `became`. */
    public static function answered(string $said, string $became): self
    {
        return new self(new TheAskAnswered($said, $became), null);
    }

    /** The member's session has ended. */
    public static function theSessionEnded(): self
    {
        return new self(null, null);
    }

    /** Something stood in the way. */
    public static function stoppedBy(WhatStoppedAMember $stopped): self
    {
        return $stopped->endsTheSession() ? self::theSessionEnded() : new self(null, $stopped);
    }

    /** Whether the member is still signed in: the core answered, or something other than the session stood in the way. */
    public function isSignedIn(): bool
    {
        return $this->answer instanceof TheAskAnswered || $this->stopped instanceof WhatStoppedAMember;
    }

    /** Whether the core answered and nothing stood in the way. */
    public function cameBack(): bool
    {
        return $this->answer instanceof TheAskAnswered;
    }
}
