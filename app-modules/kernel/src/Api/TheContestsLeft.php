<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every ask an install would leave contested.
 *
 * @implements IteratorAggregate<int, ACapabilityLeftContested>
 */
final readonly class TheContestsLeft implements IteratorAggregate
{
    /** @param list<ACapabilityLeftContested> $contests */
    private function __construct(private array $contests) {}

    /** These, in the stack's order. */
    public static function these(ACapabilityLeftContested ...$contests): self
    {
        return new self(array_values($contests));
    }

    /** @return Traversable<int, ACapabilityLeftContested> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->contests);
    }
}
