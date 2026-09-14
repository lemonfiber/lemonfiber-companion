<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * What became of every repair in a listing the operator agreed to.
 *
 * A typed collection rather than an array (`D1`), holding the order the stack
 * carried them out in — which is information: a repair that stopped may be why
 * the next one declined, and the other way round they read as two unrelated
 * disappointments.
 *
 * Empty is a legitimate value and not a happy one. An agreement the stack
 * carried out nothing for is a real answer, and it is told apart from a job
 * that ended by {@see HowTheRepairIsGoing} rather than by the count here.
 *
 * @implements IteratorAggregate<int, Mended>
 */
final readonly class WhatWasMended implements IteratorAggregate
{
    /** @param array<int, Mended> $mended */
    private function __construct(private array $mended) {}

    public static function of(Mended ...$mended): self
    {
        // Not habit: a variadic collected from named arguments has string keys,
        // and this is stored and handed out through the iterator below, so the
        // keys escape.
        return new self(array_values($mended));
    }

    public static function none(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->mended);
    }

    /**
     * How many of them changed anything on the machine.
     *
     * Counted here rather than at each screen that wants it, so the line
     * between *something happened* and *nothing did* is drawn once — by
     * {@see WhatBecameOfIt::changedSomething()} — and two surfaces cannot come
     * to disagree about whether a repair run did anything.
     */
    public function changed(): int
    {
        $changed = 0;

        foreach ($this->mended as $one) {
            if ($one->changedSomething()) {
                $changed++;
            }
        }

        return $changed;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->mended);
    }
}
