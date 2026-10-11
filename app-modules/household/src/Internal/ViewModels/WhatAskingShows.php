<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * What asking for a found title would come to, or came to, as a member is told it.
 *
 * `said` is the core's sentence, shown as it came. `became` is the catalogue
 * key for what an ask became, and is empty for a rehearsal, which asked for
 * nothing.
 */
final readonly class WhatAskingShows
{
    private function __construct(
        public bool $isSignedIn,
        public string $said,
        public string $became,
        public ?WhatStoppedAMember $stopped,
    ) {}

    /** What the core said asking would come to, or what it came to under `became`. */
    public static function answered(string $said, string $became): self
    {
        return new self(isSignedIn: true, said: $said, became: $became, stopped: null);
    }

    /** The member's session has ended. */
    public static function theSessionEnded(): self
    {
        return new self(isSignedIn: false, said: '', became: '', stopped: null);
    }

    /** Something stood in the way. */
    public static function stoppedBy(WhatStoppedAMember $stopped): self
    {
        return $stopped->endsTheSession() ? self::theSessionEnded() : new self(isSignedIn: true, said: '', became: '', stopped: $stopped);
    }

    /** Whether the core answered and nothing stood in the way. */
    public function cameBack(): bool
    {
        return $this->isSignedIn && ! $this->stopped instanceof WhatStoppedAMember;
    }
}
