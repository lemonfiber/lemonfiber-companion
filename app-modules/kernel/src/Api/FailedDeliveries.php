<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Override;
use Traversable;

/**
 * Every delivery a back-end failed since its last confirmed one, newest first.
 *
 * @implements IteratorAggregate<int, ADelivery>
 */
final readonly class FailedDeliveries implements Countable, IteratorAggregate
{
    /** @param list<ADelivery> $failed */
    private function __construct(private array $failed) {}

    public static function these(ADelivery ...$failed): self
    {
        return new self(array_values($failed));
    }


    #[Override]
    public function count(): int
    {
        return count($this->failed);
    }

    /** @return Traversable<int, ADelivery> */
    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->failed);
    }
}
