<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every capability that would have nothing filling it once a plugin goes.
 *
 * @implements IteratorAggregate<int, ACapabilityLeftUnfilled>
 */
final readonly class TheCapabilitiesLeftUnfilled implements IteratorAggregate
{
    /** @param list<ACapabilityLeftUnfilled> $unfilled */
    private function __construct(private array $unfilled) {}

    /** These, in the stack's order. */
    public static function these(ACapabilityLeftUnfilled ...$unfilled): self
    {
        return new self(array_values($unfilled));
    }

    /** @return Traversable<int, ACapabilityLeftUnfilled> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->unfilled);
    }
}
