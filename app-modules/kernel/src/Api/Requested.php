<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * What a household has asked its stack for.
 *
 * A typed collection rather than an array, which is what `D1` asks for and
 * what makes the order part of the type's promise. The order here is the one
 * the stack listed, which is the order the requests were made — and that is
 * information: the person who asked remembers *theirs*, by roughly where it
 * falls, not by how urgent an app decided it was.
 *
 * Empty is a legitimate value and one of the answers this screen exists to
 * give. A household that has asked for nothing is a quiet week rather than a
 * missing list, and an empty collection says so without a null anywhere (`C2`)
 * — which is what keeps it apart from a stack that could not be asked.
 *
 * @implements IteratorAggregate<int, Wanted>
 */
final readonly class Requested implements IteratorAggregate
{
    /** @param array<int, Wanted> $wanted */
    private function __construct(private array $wanted) {}

    public static function of(Wanted ...$wanted): self
    {
        // Not habit: a variadic collected from named arguments has string keys,
        // and everything below reads this by position.
        return new self(array_values($wanted));
    }

    public static function none(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->wanted);
    }

    /**
     * How many of them are waiting on the operator.
     *
     * Counted here rather than at each screen that wants it, so two surfaces
     * cannot disagree about what is waiting — the argument
     * {@see Waiting::wantsADecision()} makes about drawing the line once.
     */
    public function waiting(): int
    {
        $waiting = 0;

        foreach ($this->wanted as $one) {
            if ($one->standing()->wantsADecision()) {
                $waiting++;
            }
        }

        return $waiting;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->wanted);
    }
}
