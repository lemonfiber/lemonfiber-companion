<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every download still coming down inside what a stop would stop, in the stack's order.
 *
 * @implements IteratorAggregate<int, ADownloadItWouldInterrupt>
 */
final readonly class TheDownloadsComingDown implements IteratorAggregate
{
    /** @param list<ADownloadItWouldInterrupt> $downloads */
    private function __construct(private array $downloads) {}

    /** These, in the stack's order. */
    public static function these(ADownloadItWouldInterrupt ...$downloads): self
    {
        return new self(array_values($downloads));
    }

    /** @return Traversable<int, ADownloadItWouldInterrupt> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->downloads);
    }

}
