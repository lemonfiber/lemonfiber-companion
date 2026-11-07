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
 * The parts a start offers to install from this build, in the stack's order.
 *
 * @implements IteratorAggregate<int, AnInstallAStartOffers>
 */
final readonly class TheInstallsAStartOffers implements Countable, IteratorAggregate
{
    /** @param list<AnInstallAStartOffers> $installs */
    private function __construct(private array $installs) {}

    /** Each install, in order. */
    public static function these(AnInstallAStartOffers ...$installs): self
    {
        return new self(array_values($installs));
    }

    /** @return Traversable<int, AnInstallAStartOffers> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->installs);
    }

    public function count(): int
    {
        return count($this->installs);
    }
}
