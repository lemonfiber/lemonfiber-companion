<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every part the plain Compose words alone do not bring up as lemonfiber does, in the order the stack sent them.
 *
 * @implements IteratorAggregate<int, APartTheWordsLeaveOut>
 */
final readonly class ThePartsTheWordsLeaveOut implements IteratorAggregate
{
    /** @param list<APartTheWordsLeaveOut> $parts */
    private function __construct(private array $parts) {}

    /** These parts. */
    public static function these(APartTheWordsLeaveOut ...$parts): self
    {
        return new self(array_values($parts));
    }

    /** @return Traversable<int, APartTheWordsLeaveOut> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->parts);
    }
}
