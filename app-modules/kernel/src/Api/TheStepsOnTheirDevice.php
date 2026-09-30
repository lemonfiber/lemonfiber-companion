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
 * Every step the person takes on their device, in the order they take them, as the stack wrote them.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class TheStepsOnTheirDevice implements Countable, IteratorAggregate
{
    /** @param list<string> $each */
    private function __construct(private array $each) {}

    /** These, in the stack's order. */
    public static function of(string ...$each): self
    {
        foreach ($each as $one) {
            if (trim($one) === '') {
                throw HandoffSaysNothing::about('step');
            }
        }

        return new self(array_values($each));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->each);
    }

    public function count(): int
    {
        return count($this->each);
    }
}
