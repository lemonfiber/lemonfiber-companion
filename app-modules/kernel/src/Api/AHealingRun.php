<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * One healing run: a pass that could act, when it ran, whether it was a rehearsal, and every item it acted on.
 *
 * Iterating it walks those items.
 *
 * @implements IteratorAggregate<int, AnItemActedOn>
 */
final readonly class AHealingRun implements IteratorAggregate
{
    /** @param list<AnItemActedOn> $items */
    private function __construct(private Instant $at, private bool $rehearsed, private array $items) {}

    /** A run that acted, at this moment, on these items. */
    public static function ranAt(Instant $at, AnItemActedOn ...$items): self
    {
        return new self($at, rehearsed: false, items: array_values($items));
    }

    /** A rehearsal, at this moment, which acted on nothing and says what it would have done to these items. */
    public static function rehearsedAt(Instant $at, AnItemActedOn ...$items): self
    {
        return new self($at, rehearsed: true, items: array_values($items));
    }

    /** When it ran. */
    public function at(): Instant
    {
        return $this->at;
    }

    /** Whether it was a rehearsal, which acted on nothing. */
    public function wasRehearsed(): bool
    {
        return $this->rehearsed;
    }

    /** @return Traversable<int, AnItemActedOn> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
