<?php

declare(strict_types=1);

namespace Modules\News\Api;

use Modules\Kernel\Api\WhichTab;

/**
 * Which of a stack's tabs hold something new, and how many.
 *
 * An update belongs to Updates and a problem to Health, which is where each is
 * read; a request belongs to no tab of the operator's, so it marks none. A tab
 * holding nothing new carries no mark.
 */
final readonly class TheTabsMarked
{
    private function __construct(private int $updates, private int $problems) {}

    /** No tab holds anything new, or nothing has been heard to say otherwise. */
    public static function none(): self
    {
        return new self(0, 0);
    }

    /** So many new updates and so many new problems. */
    public static function holding(WhatIsNew $updates, WhatIsNew $problems): self
    {
        return new self($updates->count(), $problems->count());
    }

    /** How many new items the tab holds, which is none for a tab nothing new belongs to. */
    public function howManyOn(WhichTab $tab): int
    {
        return match ($tab) {
            WhichTab::Updates => $this->updates,
            WhichTab::Health => $this->problems,
            WhichTab::Services, WhichTab::Repairs => 0,
        };
    }
}
