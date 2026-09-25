<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

use function trim;

/**
 * What a walkthrough suggests walking instead, where nothing was chosen.
 *
 * The stack's safe first attempts, each a title as the stack names it.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class WhatCouldBeWalkedInstead implements Countable, IteratorAggregate
{
    /** @param list<string> $suggestions */
    private function __construct(private array $suggestions) {}

    /** In the stack's order; a blank one is refused. */
    public static function of(string ...$suggestions): self
    {
        foreach ($suggestions as $suggestion) {
            if (trim($suggestion) === '') {
                throw TheWalkthroughSaysNothing::about('suggestions');
            }
        }

        return new self(array_values($suggestions));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->suggestions);
    }

    public function count(): int
    {
        return count($this->suggestions);
    }
}
