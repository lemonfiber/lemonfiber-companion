<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every file a support bundle holds, in the stack's order.
 *
 * All of them or none: a bundle drawn without one of its files is one an
 * operator hands over believing they have read all of it.
 *
 * @implements IteratorAggregate<int, APieceOfABundle>
 */
final readonly class ThePiecesOfABundle implements IteratorAggregate
{
    /** @param list<APieceOfABundle> $pieces */
    private function __construct(private array $pieces) {}

    /** These files, in the stack's order; reindexed for a named spread's keys. */
    public static function of(APieceOfABundle ...$pieces): self
    {
        return new self(array_values($pieces));
    }

    /** @return Traversable<int, APieceOfABundle> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->pieces);
    }
}
