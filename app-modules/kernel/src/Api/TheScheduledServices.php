<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every run-and-exit service the stack reports, in the order it listed them.
 *
 * @implements IteratorAggregate<int, ARunAndExit>
 */
final readonly class TheScheduledServices implements IteratorAggregate
{
    /** @param list<ARunAndExit> $services */
    private function __construct(private array $services) {}

    /** These services, in the stack's order. */
    public static function these(ARunAndExit ...$services): self
    {
        return new self(array_values($services));
    }

    /** @return Traversable<int, ARunAndExit> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->services);
    }
}
