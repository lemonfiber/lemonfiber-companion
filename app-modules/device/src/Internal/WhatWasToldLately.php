<?php

declare(strict_types=1);

namespace Modules\Device\Internal;

use function array_key_exists;
use function count;

use Modules\Kernel\Api\Instant;

/**
 * When each stack's notifications were shown, over the last minute or so.
 *
 * Held for the life of the process, which is the life of the app, so the cap
 * counts across every screen. Each stack is known only by the key its
 * notifications are filed under, which names neither it nor its alerts.
 */
final class WhatWasToldLately
{
    /** @var array<string, list<int>> each stack's key, and when each of its notifications was shown, in seconds since the epoch */
    private array $shown = [];

    /** How many of a stack's notifications were shown since a moment. */
    public function since(string $stack, Instant $since): int
    {
        $this->forgetBefore($stack, $since);

        return count($this->shown[$stack]);
    }

    /** That one of a stack's notifications was shown at a moment. */
    public function told(string $stack, Instant $at): void
    {
        $this->shown[$stack][] = $at->epochSeconds();
    }

    private function forgetBefore(string $stack, Instant $since): void
    {
        $kept = [];

        foreach (array_key_exists($stack, $this->shown) ? $this->shown[$stack] : [] as $at) {
            if ($at > $since->epochSeconds()) {
                $kept[] = $at;
            }
        }

        $this->shown[$stack] = $kept;
    }
}
