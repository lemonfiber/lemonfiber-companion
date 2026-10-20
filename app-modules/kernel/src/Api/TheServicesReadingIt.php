<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_any;
use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every service reading one setting or secret, in the stack's order.
 *
 * @implements IteratorAggregate<int, AServiceReadingIt>
 */
final readonly class TheServicesReadingIt implements IteratorAggregate
{
    /** @param list<AServiceReadingIt> $readers */
    private function __construct(private array $readers) {}

    /** These readers, in the stack's order. */
    public static function these(AServiceReadingIt ...$readers): self
    {
        return new self(array_values($readers));
    }

    /** Whether any of these services reads it. */
    public function includeAnyOf(Services $services): bool
    {
        return array_any($this->readers, static fn(AServiceReadingIt $reader): bool => $services->includes($reader->service()));
    }

    /** @return Traversable<int, AServiceReadingIt> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->readers);
    }
}
