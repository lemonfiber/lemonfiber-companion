<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * The genres the media server files a title under, in its words and its order.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class Genres implements IteratorAggregate
{
    /** @param list<string> $named */
    private function __construct(private array $named) {}

    public static function of(string ...$named): self
    {
        return new self(array_values($named));
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->named);
    }
}
