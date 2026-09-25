<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Every kind of device the stack has advice for, in the order somebody is likely to be holding one.
 *
 * @implements IteratorAggregate<int, ADeviceToWatchOn>
 */
final readonly class TheDevices implements Countable, IteratorAggregate
{
    /** @param list<ADeviceToWatchOn> $devices */
    private function __construct(private array $devices) {}

    /** These devices, in the stack's order; reindexed for a named spread's keys. */
    public static function of(ADeviceToWatchOn ...$devices): self
    {
        return new self(array_values($devices));
    }

    /** @return Traversable<int, ADeviceToWatchOn> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->devices);
    }

    public function count(): int
    {
        return count($this->devices);
    }
}
