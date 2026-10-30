<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use function array_merge;

use Closure;
use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\WhatToLookFor;

/**
 * The titles a member's search found so far: every page read, in order.
 *
 * `next` is where the page after the last one read begins, or null once the
 * last page is read.
 */
final readonly class TheTitlesFound
{
    /** @param list<AFoundTitleAsShown> $rows */
    public function __construct(
        public WhatToLookFor $words,
        public string $approval,
        public array $rows,
        public ?string $next,
    ) {}

    /** These, with the page after them added beneath. */
    public function andThen(self $page): self
    {
        return new self($this->words, $page->approval, array_merge($this->rows, $page->rows), $page->next);
    }

    /** These, with no page read after them. */
    public function last(): self
    {
        return new self($this->words, $this->approval, $this->rows, null);
    }

    /**
     * These, each row's poster drawn from the source `$from` answers for its title.
     *
     * @param Closure(AFoundTitle): ?string $from
     */
    public function pictured(Closure $from): self
    {
        $rows = [];

        foreach ($this->rows as $row) {
            $rows[] = $row->pictured($from($row->found));
        }

        return new self($this->words, $this->approval, $rows, $this->next);
    }
}
