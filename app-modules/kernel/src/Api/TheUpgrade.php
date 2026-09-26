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
 * Upgrading what is already in the library, kind by kind: described, or carried out.
 *
 * Described is what it would come to, with nothing fetched. Carried out is the
 * same kinds with what each service was asked. The two are two constructors
 * rather than a flag a caller passes, so which one a screen holds is decided
 * where the stack's answer is read.
 *
 * @implements IteratorAggregate<int, OneKindUpgraded>
 */
final readonly class TheUpgrade implements Countable, IteratorAggregate
{
    /** @param list<OneKindUpgraded> $kinds */
    private function __construct(private array $kinds, private bool $carriedOut) {}

    /** What upgrading would come to; nothing was asked of any service. */
    public static function described(OneKindUpgraded ...$kinds): self
    {
        return new self(array_values($kinds), carriedOut: false);
    }

    /** What upgrading did, the operator having said yes. */
    public static function carriedOut(OneKindUpgraded ...$kinds): self
    {
        return new self(array_values($kinds), carriedOut: true);
    }

    /** Whether it was carried out rather than only described. */
    public function wasCarriedOut(): bool
    {
        return $this->carriedOut;
    }

    /** @return Traversable<int, OneKindUpgraded> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->kinds);
    }

    public function count(): int
    {
        return count($this->kinds);
    }
}
