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
 * The presets in force, in the stack's order: the overall choice first, then each kind of media set apart from it.
 *
 * @implements IteratorAggregate<int, APresetInForce>
 */
final readonly class ThePresetsInForce implements Countable, IteratorAggregate
{
    /** @param list<APresetInForce> $presets */
    private function __construct(private array $presets) {}

    /** These choices, in the stack's order; reindexed for a named spread's keys. */
    public static function of(APresetInForce ...$presets): self
    {
        return new self(array_values($presets));
    }

    /** @return Traversable<int, APresetInForce> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->presets);
    }

    public function count(): int
    {
        return count($this->presets);
    }
}
