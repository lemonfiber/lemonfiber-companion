<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use Closure;
use Modules\Kernel\Api\AFoundTitle;

/**
 * What a member's search has shown so far: the titles it found, what stopped it, or both when a later page did not come back.
 */
final readonly class WhatTheSearchShows
{
    private function __construct(
        public bool $isSignedIn,
        public ?TheTitlesFound $found,
        public ?WhatStoppedAMember $stopped,
    ) {}

    /** Nothing searched for yet. */
    public static function nothingYet(): self
    {
        return new self(isSignedIn: true, found: null, stopped: null);
    }

    /** A page the core answered. */
    public static function page(TheTitlesFound $found): self
    {
        return new self(isSignedIn: true, found: $found, stopped: null);
    }

    /** The member's session has ended. */
    public static function theSessionEnded(): self
    {
        return new self(isSignedIn: false, found: null, stopped: null);
    }

    /** Something stood in the way. */
    public static function stoppedBy(WhatStoppedAMember $stopped): self
    {
        return $stopped->endsTheSession()
            ? self::theSessionEnded()
            : new self(isSignedIn: true, found: null, stopped: $stopped);
    }

    /** This page beneath the titles `$before` it; a page that did not come back keeps them, with what stopped it. */
    public function after(TheTitlesFound $before): self
    {
        return new self(
            isSignedIn: $this->isSignedIn,
            found: $this->found instanceof TheTitlesFound ? $before->andThen($this->found) : $before->last(),
            stopped: $this->stopped,
        );
    }

    /**
     * This, each row's poster drawn from the source `$from` answers for its title.
     *
     * @param Closure(AFoundTitle): ?string $from
     */
    public function pictured(Closure $from): self
    {
        return new self(isSignedIn: $this->isSignedIn, found: $this->found?->pictured($from), stopped: $this->stopped);
    }

    /** Whether a search was answered or stopped. */
    public function searched(): bool
    {
        return $this->found instanceof TheTitlesFound || $this->stopped instanceof WhatStoppedAMember;
    }
}
