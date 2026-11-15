<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * What the outside watcher sees: whether it answers, every monitor it holds, and what no active monitor covers.
 *
 * Iterating it walks the monitors in the order the stack sent them.
 *
 * @implements IteratorAggregate<int, AMonitor>
 */
final readonly class WhatTheWatcherSees implements IteratorAggregate
{
    /** @param list<AMonitor> $monitors */
    private function __construct(
        private string $watcher,
        private string $blindBecause,
        private Instant $readAt,
        private array $monitors,
        private WhatNoMonitorCovers $uncovered,
    ) {}

    /** A watcher that answered. */
    public static function answering(string $watcher, Instant $readAt, WhatNoMonitorCovers $uncovered, AMonitor ...$monitors): self
    {
        return new self($watcher, '', $readAt, array_values($monitors), $uncovered);
    }

    /** A watcher that does not answer, or that nothing fills, with the stack's reason. */
    public static function blind(string $watcher, string $because, Instant $readAt, WhatNoMonitorCovers $uncovered): self
    {
        return new self($watcher, $because, $readAt, [], $uncovered);
    }

    /** The plugin watching, or empty where nothing fills the capability. */
    public function watcher(): string
    {
        return $this->watcher;
    }

    /** Whether the watcher answered. */
    public function answers(): bool
    {
        return $this->blindBecause === '';
    }

    /** Why it does not answer, empty where it does. */
    public function blindBecause(): string
    {
        return $this->blindBecause;
    }

    /** When the stack asked it. */
    public function readAt(): Instant
    {
        return $this->readAt;
    }

    /** Every critical service and household entry point no active monitor covers. */
    public function uncovered(): WhatNoMonitorCovers
    {
        return $this->uncovered;
    }

    /** @return Traversable<int, AMonitor> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->monitors);
    }
}
