<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use function array_merge;

/**
 * What a member's search has shown so far: every page read, in order, or what stopped it.
 *
 * Pages are added as the member scrolls, so the rows are every page's titles
 * and `next` is where the page after the last one read begins, or nothing.
 */
final readonly class WhatTheSearchShows
{
    /** @param list<AFoundTitleAsShown> $rows */
    private function __construct(
        public bool $searched,
        public bool $isSignedIn,
        public string $approval,
        public array $rows,
        public string $next,
        public ?WhatStoppedAMember $stopped,
    ) {}

    /** Nothing searched for yet. */
    public static function nothingYet(): self
    {
        return new self(searched: false, isSignedIn: true, approval: '', rows: [], next: '', stopped: null);
    }

    /**
     * A page the core answered.
     *
     * @param list<AFoundTitleAsShown> $rows
     */
    public static function page(string $approval, array $rows, string $next): self
    {
        return new self(searched: true, isSignedIn: true, approval: $approval, rows: $rows, next: $next, stopped: null);
    }

    /** The member's session has ended. */
    public static function theSessionEnded(): self
    {
        return new self(searched: true, isSignedIn: false, approval: '', rows: [], next: '', stopped: null);
    }

    /** Something stood in the way. */
    public static function stoppedBy(WhatStoppedAMember $stopped): self
    {
        return $stopped->endsTheSession()
            ? self::theSessionEnded()
            : new self(searched: true, isSignedIn: true, approval: '', rows: [], next: '', stopped: $stopped);
    }

    /**
     * This, with the page after it added; a page that did not come back is kept as what stopped it, beneath what was already shown.
     */
    public function andThen(self $page): self
    {
        if (! $page->cameBack()) {
            return new self(searched: true, isSignedIn: $page->isSignedIn, approval: $this->approval, rows: $this->rows, next: '', stopped: $page->stopped);
        }

        return new self(searched: true, isSignedIn: true, approval: $page->approval, rows: array_merge($this->rows, $page->rows), next: $page->next, stopped: null);
    }

    /** Whether the core answered and nothing stood in the way. */
    public function cameBack(): bool
    {
        return $this->isSignedIn && ! $this->stopped instanceof WhatStoppedAMember;
    }

    /** Whether another page follows the last one read. */
    public function hasMore(): bool
    {
        return $this->next !== '';
    }
}
