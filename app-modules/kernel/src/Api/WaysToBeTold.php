<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use ArrayIterator;
use IteratorAggregate;
use Override;
use Traversable;

/**
 * The ways the operator offered the household to be told, in the core's order, and whether this phone is registered to be woken.
 *
 * @implements IteratorAggregate<int, AWayToBeTold>
 */
final readonly class WaysToBeTold implements IteratorAggregate
{
    /** @param list<AWayToBeTold> $ways */
    private function __construct(private array $ways, private bool $phone) {}

    /** @param list<AWayToBeTold> $ways */
    public static function offered(bool $phone, array $ways): self
    {
        return new self($ways, $phone);
    }

    /** Whether nothing is offered, where the choice is not drawn. */
    public function isEmpty(): bool
    {
        return $this->ways === [];
    }

    /** Whether this phone is registered to be woken. */
    public function wakesThisPhone(): bool
    {
        return $this->phone;
    }

    /** @return Traversable<int, AWayToBeTold> */
    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->ways);
    }
}
