<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Override;
use Traversable;

/**
 * Every back-end whose record the core could not read.
 *
 * @implements IteratorAggregate<int, AnUnreadBackEnd>
 */
final readonly class UnreadBackEnds implements IteratorAggregate
{
    /** @param list<AnUnreadBackEnd> $unread */
    private function __construct(private array $unread) {}

    public static function these(AnUnreadBackEnd ...$unread): self
    {
        return new self(array_values($unread));
    }

    public function isEmpty(): bool
    {
        return $this->unread === [];
    }

    /** @return Traversable<int, AnUnreadBackEnd> */
    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->unread);
    }
}
