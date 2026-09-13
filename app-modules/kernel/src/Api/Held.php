<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a screen was holding when the operator left it (`N1-R38`).
 *
 * The requirement has two clauses and they pull in opposite directions.
 * Returning to a screen must restore what was done there — and must **not**
 * re-read the stack solely to rebuild it. The first says the screen must look
 * the way it looked; the second refuses the easiest way to make that true.
 *
 * The easy way is to re-run whatever built the screen the first time. It looks
 * correct, and on a desk with a stack on the same subnet it is indistinguishable
 * from correct. What it actually does is discard the operator's half-finished
 * work — the text they had typed, the rows they had picked — and replace it with
 * whatever the stack says now, which is a different thing and arrives after a
 * wait. `ADR-0020` makes the same argument about actions; this is the reading
 * side of it.
 *
 * So the state travels rather than being rebuilt, and the two arms are the two
 * situations a screen is actually in: this is the first visit, or it is a
 * return. They take different arguments, which is what stops a caller reading
 * one as the other — a `bool` here would make "nothing to restore" and
 * "restore this" the same branch, and the branch that wins is whichever the
 * screen's author tested.
 *
 * **What it holds is the surface's, not the kernel's.** An `object`, the way
 * {@see Reach} takes one: the kernel has no business knowing what a particular
 * screen keeps, and a closed set here would have to be edited by anybody adding
 * a screen. What the kernel does enforce is the second clause — nothing in this
 * type names an interface, so a `Held` cannot be handed a port and therefore
 * cannot refetch anything. That is checked, in `TypesThatMustNotMeetTest`.
 */
final readonly class Held
{
    private function __construct(private Whereabouts $was, private ?object $keeping) {}

    /**
     * The operator is arriving here for the first time.
     *
     * Nothing to restore, and the arm says so rather than handing back an empty
     * something — an empty payload and a real one have the same shape, and a
     * screen that cannot tell them apart renders the empty one as though the
     * operator had cleared the form themselves.
     */
    public static function nothingYet(Whereabouts $was): self
    {
        return new self($was, null);
    }

    /**
     * The operator was here before, and this is what they had done.
     */
    public static function keeping(Whereabouts $was, object $keeping): self
    {
        return new self($was, $keeping);
    }

    /**
     * Which screen this belongs to.
     *
     * On both arms, because restoring the wrong screen's work is worse than
     * restoring none: it puts one screen's half-finished input in front of
     * somebody looking at another.
     */
    public function was(): Whereabouts
    {
        return $this->was;
    }

    /**
     * @template TFresh of object
     * @template TRestored of object
     *
     * @param  Closure(Whereabouts): TFresh  $fresh
     * @param  Closure(object, Whereabouts): TRestored  $restored
     * @return TFresh|TRestored
     */
    public function either(Closure $fresh, Closure $restored): object
    {
        // Read off the restore, the way `Kept` and `Interrupted` do: the arm
        // carrying something is the one the type is written around, and a
        // fall-through is how a branch becomes the one nobody tested.
        // `!== null` rather than the `instanceof` its siblings use, because
        // there is no class to name: what a screen keeps is the surface's, and
        // the kernel holding a type for it would be the kernel knowing the
        // screens.
        return $this->keeping === null
            ? $fresh($this->was)
            : $restored($this->keeping, $this->was);
    }
}
