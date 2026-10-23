<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Internal;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Traversable;

/**
 * The stacks a wake is still listening to, each with the operator's session for it.
 *
 * Immutable: a stack is added by answering a longer list. Walked in the order
 * the stacks were added.
 *
 * @implements IteratorAggregate<int, array{Stack, Session}>
 */
final readonly class TheStacksToHear implements Countable, IteratorAggregate
{
    /** @param list<array{Stack, Session}> $stacks */
    private function __construct(private array $stacks) {}

    /** No stack yet. */
    public static function none(): self
    {
        return new self([]);
    }

    /** The same, with this stack and the session it is heard with. */
    public function with(Stack $stack, Session $session): self
    {
        return new self([...$this->stacks, [$stack, $session]]);
    }

    /** Whether no stack is left to hear. */
    public function isEmpty(): bool
    {
        return $this->stacks === [];
    }

    public function count(): int
    {
        return count($this->stacks);
    }

    /** @return Traversable<int, array{Stack, Session}> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->stacks);
    }
}
