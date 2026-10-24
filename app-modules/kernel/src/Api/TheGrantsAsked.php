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
 * Every credential of another service an install or update would hand a third-party plugin, in the stack's order.
 *
 * None at all is an answer: a first-party plugin, or one that asks for no
 * other service's credential, is handed none.
 *
 * @implements IteratorAggregate<int, AGrantAsked>
 */
final readonly class TheGrantsAsked implements Countable, IteratorAggregate
{
    /** @param list<AGrantAsked> $asked */
    private function __construct(private array $asked) {}

    /** These grants, in the stack's order; reindexed for a named spread's keys. */
    public static function these(AGrantAsked ...$asked): self
    {
        return new self(array_values($asked));
    }

    /** Every line their approvals are given by, as the stack spells each. */
    public function approvals(): PluginLines
    {
        $approvals = [];

        foreach ($this->asked as $asked) {
            $approvals[] = $asked->approval();
        }

        return PluginLines::under('approval', ...$approvals);
    }

    /** @return Traversable<int, AGrantAsked> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->asked);
    }

    public function count(): int
    {
        return count($this->asked);
    }
}
