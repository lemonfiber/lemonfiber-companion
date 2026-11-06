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
 * The starting files whose placeholders an update changes, in the stack's order.
 *
 * @implements IteratorAggregate<int, AFileReshipped>
 */
final readonly class TheFilesReshipped implements Countable, IteratorAggregate
{
    /** @param list<AFileReshipped> $files */
    private function __construct(private array $files) {}

    /** Each file, in order. */
    public static function these(AFileReshipped ...$files): self
    {
        return new self(array_values($files));
    }

    /** @return Traversable<int, AFileReshipped> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->files);
    }

    public function count(): int
    {
        return count($this->files);
    }
}
