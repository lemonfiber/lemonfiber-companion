<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_any;
use function array_map;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * The stacks this device has been introduced to.
 *
 * Three things are asked for and this is where two of them stop being
 * promises: the app holds more than one configured stack, and a reading from
 * one is never attributed to another. The third — that each stack's session is
 * kept separate — is deliberately not here, for the reason
 * {@see Stack} gives: a session is the thing the app may not retain, and
 * putting it beside the thing the app does retain
 * makes the first piece of code to write one out take the other with it.
 *
 * **There is no current stack, and that is the design.** The obvious shape for
 * this is a list with a selection on it, and every screen then reads "the"
 * stack from somewhere shared. That is refused exactly: which stack a
 * screen is showing is carried by that screen. So nothing here answers *which
 * one* — a caller that wants a stack names it, and naming one this device does
 * not hold raises {@see StackIsNotConfigured} rather than falling back to the
 * first, the only, or whatever a previous screen left behind. Those three
 * fallbacks are what the last clause is about, and each of them is one
 * line somebody writes while fixing something else.
 *
 * **Immutable, like everything else a capability holds.** `with()` answers a
 * new record. A mutable list is a thing two screens can hold at once and
 * disagree about, which is the shared state the rule is written against.
 *
 * Identity is compared with {@see StackId::is()} rather than by array key, even
 * though a map keyed on the stored identifier would be shorter. That method
 * compares in constant time and sits beside the ones where that matters, and
 * the reason it does is that a second way of asking "is this the same stack"
 * is a second way for the wrong one to get copied. A device holds a handful of
 * stacks; walking them costs nothing worth having two answers for.
 *
 * @implements IteratorAggregate<int, Stack>
 */
final readonly class Configured implements IteratorAggregate
{
    /** @param list<Stack> $stacks */
    private function __construct(private array $stacks) {}

    /**
     * A device that has been introduced to nothing.
     *
     * The state a first launch is in, and a legitimate value rather than a
     * missing one — there is a whole screen about it, so this cannot be an
     * absence something has to remember to check for.
     */
    public static function none(): self
    {
        return new self([]);
    }

    /**
     * Read back from what the device retained.
     *
     * Folded through `with()` rather than taken as given, so the rule about a
     * repeated identifier is written once. Retained state holding two entries
     * for one stack is a corrupt list, and the two entries are the same stack
     * by the only definition of sameness there is — collapsing them to the
     * later one is the reading that cannot attribute anything to the wrong
     * machine.
     */
    public static function of(Stack ...$stacks): self
    {
        $record = self::none();

        foreach ($stacks as $stack) {
            $record = $record->with($stack);
        }

        return $record;
    }

    /**
     * The same record, now holding this stack.
     *
     * A stack whose identifier is already held replaces it **in its place**.
     * That is re-pairing: trust is pinned to the stack rather than to where
     * it answers, so a machine that comes back on another address, or with a
     * renewed certificate, is the same machine and must not arrive as a second
     * entry. Keeping its position matters for the same reason a second entry
     * would not do: the operator recognises their list by its order, and a list
     * that rearranges itself after a re-pair reads as something having gone
     * wrong.
     */
    public function with(Stack $stack): self
    {
        // Mapped rather than assigned at the position the loop found, which is
        // the shorter code and leaves the analyser unable to prove the result
        // is still a list. Reusing `knows()` is worth more than the line it
        // costs anyway: there is one definition of *is this the same stack* and
        // this is not a second one.
        if ($this->knows($stack->id())) {
            return new self(array_map(
                static fn(Stack $held): Stack => $held->id()->is($stack->id()) ? $stack : $held,
                $this->stacks,
            ));
        }

        return new self([...$this->stacks, $stack]);
    }

    /** Whether this device has been introduced to the stack named. */
    public function knows(StackId $id): bool
    {
        return array_any($this->stacks, fn(Stack $held): bool => $held->id()->is($id));
    }

    /**
     * The stack named, or a refusal — never a substitute.
     *
     * Raises {@see StackIsNotConfigured} where this device holds no such stack.
     *
     * **No `@throws`, deliberately**, which is the house rule `Problems`
     * records and {@see Pairing} follows for the same
     * reason: an `@throws` makes the exception *checked* to the analyser,
     * shipmonk forbids raising a checked exception inside any closure, and
     * every Pest test body is a closure. Annotating this would make the
     * refusal the one behaviour no test could drive — and the refusal is the
     * whole point of the method.
     */
    public function stack(StackId $id): Stack
    {
        foreach ($this->stacks as $held) {
            if ($held->id()->is($id)) {
                return $held;
            }
        }

        throw StackIsNotConfigured::here($id);
    }

    /** Whether a launch reaches the first-run screen rather than an operator's. */
    public function isEmpty(): bool
    {
        return $this->stacks === [];
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->stacks);
    }
}
