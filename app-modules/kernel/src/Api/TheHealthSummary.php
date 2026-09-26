<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * The one line every surface says about a stack, and what it expands to.
 *
 * Computed by the core, once, for every surface at the same time: the word, how
 * many things want attention counted by cause rather than by symptom, the worst
 * of them named, and every affected item worst first. The app holds what came
 * back and computes none of it, which is what makes the line the same here as
 * on the machine.
 *
 * Iterating it walks the affected items in the order the core put them in.
 *
 * @implements IteratorAggregate<int, AnAffectedItem>
 */
final readonly class TheHealthSummary implements IteratorAggregate
{
    /** @param list<AnAffectedItem> $affected */
    private function __construct(
        private HowItStands $standing,
        private int $wantingAttention,
        private string $worst,
        private array $affected,
    ) {}

    /**
     * The summary as the core sent it.
     *
     * `$worst` is empty where the core named nothing, which it does where nothing
     * is wrong.
     */
    public static function of(HowItStands $standing, int $wantingAttention, string $worst, AnAffectedItem ...$affected): self
    {
        if ($wantingAttention < 0) {
            throw SummaryCountsBelowNothing::wanting($wantingAttention);
        }

        return new self($standing, $wantingAttention, $worst, array_values($affected));
    }

    public function standing(): HowItStands
    {
        return $this->standing;
    }

    public function wantingAttention(): int
    {
        return $this->wantingAttention;
    }

    public function worst(): string
    {
        return $this->worst;
    }

    /** @return Traversable<int, AnAffectedItem> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->affected);
    }
}
