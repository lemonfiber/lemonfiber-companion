<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * Everything a stack runs, and what the stack says that amounts to.
 *
 * A typed collection rather than an array (`D1`), and the order is the stack's
 * own — worst first, which is the order the contract lists in and the order an
 * operator reads. Sorting alphabetically would put a crashed service under a
 * healthy one and the operator would scroll past the row they opened the app
 * for.
 *
 * **What it all amounts to comes first.** `status.condition` is the stack's own
 * judgement, and putting it before the variadic means a listing that could be
 * built from rows alone is unspellable — the argument {@see Stalled::of()}
 * makes about completeness, applied to a verdict. A screen adding the services
 * up itself would be a second opinion about something the machine already
 * decided, and the two disagree the first time the machine weighs something
 * differently.
 *
 * **The forms are carried beside the services**, because `N2-R7` asks for start,
 * stop and restart *by form* as well as by service, and a form with no service
 * running in it still exists — a stack whose whole media form is stopped has a
 * form an operator wants to start, and deriving the list from the rows would
 * lose exactly that one.
 *
 * Empty is a legitimate value: a stack that runs nothing is `Inactive`, which
 * is a state rather than a missing list.
 *
 * @implements IteratorAggregate<int, Daemon>
 */
final readonly class Daemons implements IteratorAggregate
{
    /** @param array<int, Daemon> $daemons */
    private function __construct(
        private HowTheStackIsRunning $running,
        private Forms $forms,
        private Disturbances $disturbs,
        private array $daemons,
    ) {}

    /**
     * A listing as one stack gave it.
     *
     * Not habit on the reindex: a variadic collected from named arguments has
     * string keys, and everything below reads this by position.
     */
    public static function of(
        HowTheStackIsRunning $running,
        Forms $forms,
        Disturbances $disturbs,
        Daemon ...$daemons,
    ): self {
        return new self($running, $forms, $disturbs, array_values($daemons));
    }

    /**
     * A stack that runs nothing at all, which is a state and not a gap.
     *
     * It still says what its verbs cost. What they cost is read off how long
     * the stack is prepared to wait, which is configuration rather than state,
     * so a machine with nothing running answers the same as one with eight —
     * and a shortcut that filled it in here would be this side inventing a
     * bound, which is the whole of what `N2-R14` refuses.
     */
    public static function none(Disturbances $disturbs): self
    {
        return new self(HowTheStackIsRunning::Inactive, Forms::none(), $disturbs, []);
    }

    /** What the stack says it all amounts to. */
    public function running(): HowTheStackIsRunning
    {
        return $this->running;
    }

    /** The forms this stack has, whether or not anything in them is running. */
    public function forms(): Forms
    {
        return $this->forms;
    }

    /**
     * What each verb would take away, as the stack reported it.
     *
     * Carried on the listing because that is where the decision is made:
     * `N2-R8` wants the bound said before the operator confirms, and a screen
     * that had to fetch it when somebody tapped would either ask again mid-tap
     * or state a number from a reading it no longer holds.
     */
    public function disturbs(): Disturbances
    {
        return $this->disturbs;
    }

    public function count(): int
    {
        return count($this->daemons);
    }

    /** @return Traversable<int, Daemon> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->daemons);
    }
}
