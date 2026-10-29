<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every adapter of a plugin that writes the files of the service it fronts, as an install's reading states them.
 *
 * @implements IteratorAggregate<int, AnAdapterWritingItsUpstream>
 */
final readonly class TheUpstreamsWritten implements IteratorAggregate
{
    /** @param list<AnAdapterWritingItsUpstream> $written */
    private function __construct(private array $written) {}

    /** These, in the stack's order. */
    public static function these(AnAdapterWritingItsUpstream ...$written): self
    {
        return new self(array_values($written));
    }

    /** @return Traversable<int, AnAdapterWritingItsUpstream> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->written);
    }
}
