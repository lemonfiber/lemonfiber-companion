<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every healing run the stack keeps, newest first, rehearsed or not.
 *
 * @implements IteratorAggregate<int, AHealingRun>
 */
final readonly class TheHealingRuns implements IteratorAggregate
{
    /** @param list<AHealingRun> $runs */
    private function __construct(private array $runs) {}

    /** These runs, newest first. */
    public static function these(AHealingRun ...$runs): self
    {
        return new self(array_values($runs));
    }

    /** @return Traversable<int, AHealingRun> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->runs);
    }
}
