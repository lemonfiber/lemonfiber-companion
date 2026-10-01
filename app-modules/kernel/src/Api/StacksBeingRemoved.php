<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_any;
use function array_filter;
use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * The stacks whose removal has begun and not been finished, each once.
 *
 * @implements IteratorAggregate<int, StackId>
 */
final readonly class StacksBeingRemoved implements IteratorAggregate
{
    /** @param list<StackId> $stacks */
    private function __construct(private array $stacks) {}

    public static function none(): self
    {
        return new self([]);
    }

    /** These, and this one; a stack already among them is not added twice. */
    public function with(StackId $stack): self
    {
        return $this->holds($stack) ? $this : new self([...$this->stacks, $stack]);
    }

    /** These, without this one. */
    public function without(StackId $stack): self
    {
        return new self(array_values(array_filter($this->stacks, static fn(StackId $held): bool => ! $held->is($stack))));
    }

    public function holds(StackId $stack): bool
    {
        return array_any($this->stacks, static fn(StackId $held): bool => $held->is($stack));
    }

    public function isEmpty(): bool
    {
        return $this->stacks === [];
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->stacks);
    }
}
