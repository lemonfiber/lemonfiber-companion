<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_any;
use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * The repairs a stack offered, in the order it offered them.
 *
 * A typed collection rather than an array, which is `D1`, and the order is part
 * of the promise for {@see Remedies}' reason: the engine decided which to put
 * first and a screen re-sorting them is discarding the one thing it cannot work
 * out for itself.
 *
 * **Empty is the ordinary answer, not a failure.** Most runs offer nothing,
 * because most findings are things the operator has to go and do. The rule is
 * about what happens *where* the core offers a repair, so a stack that offers
 * none has answered the requirement rather than fallen short of it — and a
 * screen showing "no repairs available" for every healthy stack would be
 * saying something nobody asked.
 *
 * **What this collection does not do is decide.** It holds what was offered and
 * answers which finding each belongs under; whether to offer a repair, and
 * which, is the engine's. `N2` is explicit that the app renders what the core
 * already knows rather than working any of it out again.
 *
 * @implements IteratorAggregate<int, Repair>
 */
final readonly class Repairs implements IteratorAggregate
{
    /** @param array<int, Repair> $repairs */
    private function __construct(private array $repairs) {}

    public static function of(Repair ...$repairs): self
    {
        // Values rather than the variadic as given. `Remedies` does the same and
        // says it is because `likeliest()` slices by position; nothing here
        // reads by position, so that is not the reason and saying it would be
        // borrowing a justification.
        //
        // The reason here is the type. A variadic collected from named
        // arguments has string keys, and `Repairs::of(first: $a, then: $b)` is
        // a legal call — so without this the field is not the `array<int,
        // Repair>` it is declared as, and every reader that trusts the
        // declaration is trusting something that is not true.
        return new self(array_values($repairs));
    }

    /** No repair was offered, which is what most runs answer. */
    public static function none(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->repairs);
    }

    /**
     * Whether anything at all is on offer, which is what a screen asks first.
     *
     * A screen with no repairs draws no confirmation, no consequences and no
     * button, so this is the question that decides whether the rest of the
     * frame exists. Asked as its own method rather than as `count() === 0` at
     * each call site, which is the same comparison written in several places
     * and eventually one of them the wrong way round.
     */
    public function isEmpty(): bool
    {
        return $this->repairs === [];
    }

    /**
     * Whether one of these answers a given check.
     *
     * How a findings screen learns that a row has something on offer under it.
     * The check is the only thing a {@see Repair} publishes on its own, and
     * this is why: a screen matching repairs to findings has learned nothing
     * about what any of them would do, which is a repair's business and
     * {@see Repair::stated()}'s.
     */
    public function answering(Check $check): bool
    {
        return array_any(
            $this->repairs,
            static fn(Repair $repair): bool => $repair->answers()->is($check),
        );
    }

    /**
     * Whether this listing is the one a given repair came in.
     *
     * By identity rather than by value, which is {@see Confirmed}'s argument
     * about readings: two listings can offer a repair that looks the same and
     * be about different moments, and treating them as one would be this app
     * deciding on the operator's behalf that nothing important changed.
     */
    public function holds(Repair $repair): bool
    {
        return array_any(
            $this->repairs,
            static fn(Repair $held): bool => $held === $repair,
        );
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->repairs);
    }
}
