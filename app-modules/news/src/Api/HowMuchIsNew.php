<?php

declare(strict_types=1);

namespace Modules\News\Api;

use Modules\Kernel\Api\WhichTab;

/**
 * How much is new on a stack, of each kind, by what the stack named as newest.
 *
 * What the bar marks each tab with and what the menu counts. An update belongs
 * to Updates and a problem to Health, which is where each is read; a request
 * belongs to no tab of the operator's, so it marks none and is counted in all.
 * A tab holding nothing new carries no mark, and a stack holding nothing new
 * counts none.
 */
final readonly class HowMuchIsNew
{
    private function __construct(private int $updates, private int $requests, private int $problems) {}

    /** Nothing is new, or nothing has been heard to say otherwise. */
    public static function none(): self
    {
        return new self(0, 0, 0);
    }

    /** So many new updates, requests and problems. */
    public static function holding(WhatIsNew $updates, WhatIsNew $requests, WhatIsNew $problems): self
    {
        return new self($updates->count(), $requests->count(), $problems->count());
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

    /** How many new items there are of every kind, which is what the menu counts. */
    public function howManyInAll(): int
    {
        return $this->updates + $this->requests + $this->problems;
    }
}
