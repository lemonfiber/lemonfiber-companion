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
 * The UnifiedPush distributors installed on this phone, in the order the phone lists them.
 *
 * @implements IteratorAggregate<int, ADistributor>
 */
final readonly class TheDistributors implements Countable, IteratorAggregate
{
    /** @param list<ADistributor> $distributors */
    private function __construct(private array $distributors) {}

    public static function of(ADistributor ...$distributors): self
    {
        return new self(array_values($distributors));
    }

    public function count(): int
    {
        return count($this->distributors);
    }

    /** @return Traversable<int, ADistributor> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->distributors);
    }

    /** Only the one installed as the same package as `$asked`, which is none where it is not installed. */
    public function onlyThe(ADistributor $asked): self
    {
        $kept = [];

        foreach ($this->distributors as $distributor) {
            if ($distributor->is($asked)) {
                $kept[] = $distributor;
            }
        }

        return new self($kept);
    }
}
