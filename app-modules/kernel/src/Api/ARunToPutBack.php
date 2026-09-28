<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use ArrayIterator;
use Closure;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * One run as the record shows it: every change the record holds under its stamp.
 *
 * What an operator is shown before agreeing to put a run back. The stack puts
 * back the whole run and never half of one, and each row of the record says how
 * many changes its run made — so the agreement is drawn from those rows rather
 * than from anything this app counts.
 *
 * **Whether it can go back is the rows' own word.** A run holding a change the
 * record says cannot be put back is one the stack puts none of back, because it
 * judges every change before touching any. So {@see self::goesBack()} reads the
 * rows and decides nothing of its own.
 *
 * @implements IteratorAggregate<int, Change>
 */
final readonly class ARunToPutBack implements Countable, IteratorAggregate
{
    /** @param list<Change> $changes */
    private function __construct(private ARun $run, private array $changes) {}

    /**
     * The run, and the changes the record holds under its stamp, in the record's order.
     *
     * Only the ones it holds are kept, so a caller handing over a whole record
     * cannot widen what is agreed to.
     */
    public static function of(ARun $run, Change ...$changes): self
    {
        $held = [];

        foreach ($changes as $change) {
            if ($run->holds($change)) {
                $held[] = $change;
            }
        }

        return new self($run, $held);
    }

    /** The run, by its stamp. */
    public function run(): ARun
    {
        return $this->run;
    }

    /**
     * How many changes go with the run, as the record's own row says.
     *
     * Every row of one run says the same count; the first is read. Nothing
     * where the record holds nothing under the stamp.
     */
    public function alongside(): int
    {
        foreach ($this->changes as $change) {
            return $change->alongside();
        }

        return 0;
    }

    /**
     * When the run was made, where the record holds any of it.
     *
     * Every change of one run carries one stamp, so the first says it for all.
     *
     * @template TMade of object
     * @template TNowhere of object
     *
     * @param  Closure(WhenItWasMade): TMade $made
     * @param  Closure(): TNowhere           $nowhere
     * @return TMade|TNowhere
     */
    public function when(Closure $made, Closure $nowhere): object
    {
        foreach ($this->changes as $change) {
            return $made($change->when());
        }

        return $nowhere();
    }

    /**
     * Whether the stack would put this run back, on the record's own word.
     *
     * Not where the record holds nothing under the stamp, and not where any
     * change of it says it cannot be put back at all.
     */
    public function goesBack(): bool
    {
        foreach ($this->changes as $change) {
            if ($change->reversal() === HowFarItGoesBack::None) {
                return false;
            }
        }

        return $this->changes !== [];
    }

    /** @return Traversable<int, Change> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->changes);
    }

    public function count(): int
    {
        return count($this->changes);
    }
}
