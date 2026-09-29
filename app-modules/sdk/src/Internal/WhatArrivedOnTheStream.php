<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Lemonfiber\Sdk\Events\ServerEvent;
use Lemonfiber\Sdk\Generated\Kind;

/**
 * What one look at a held stream found: the events, whether anything came at all, and whether the stream ended.
 *
 * The three are apart because a screen does something different with each. A
 * heartbeat is bytes and no event, and it proves the stack is still there. An
 * event of a kind the screen is not holding the stream for proves the same. A
 * stream that ran out is closed, whatever it said before it did.
 */
final readonly class WhatArrivedOnTheStream
{
    /** @param list<ServerEvent> $events */
    public function __construct(
        private array $events,
        public bool $anything,
        public bool $ended,
    ) {}

    /** The last event of one kind among everything that arrived, where one did. */
    public function theLast(Kind $kind): ?ServerEvent
    {
        $latest = null;

        foreach ($this->events as $event) {
            if ($event->kind === $kind->value) {
                $latest = $event;
            }
        }

        return $latest;
    }
}
