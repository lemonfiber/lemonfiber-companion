<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every service the stack holds back until the operator gives what it needs to start, in the stack's order.
 *
 * None is an answer: every service the stack declares may start.
 *
 * @implements IteratorAggregate<int, AServiceWaiting>
 */
final readonly class TheServicesWaiting implements IteratorAggregate
{
    /** @param list<AServiceWaiting> $waiting */
    private function __construct(private array $waiting) {}

    /** These services, in the stack's order. */
    public static function these(AServiceWaiting ...$waiting): self
    {
        return new self(array_values($waiting));
    }

    /** None: nothing is held back. */
    public static function none(): self
    {
        return new self([]);
    }

    /** @return Traversable<int, AServiceWaiting> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->waiting);
    }
}
