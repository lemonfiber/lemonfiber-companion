<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * A look at what one service has been saying, and the edge of that look.
 *
 * `N2-R10` is three clauses and this type is where two of them stop being
 * possible to drop: a read that is bounded, that names the service, and that
 * says the view is a window rather than the whole.
 *
 * **What arrived is remembered separately from what is shown.** Searching
 * narrows the lines and must not narrow the claim: a window of two hundred
 * filtered to twelve is still a window that stopped at two hundred, and
 * recomputing the bound from the twelve would have the screen announce that the
 * search covered everything the service ever said. That is the one lie this
 * screen can tell that an operator would act on — they would conclude the error
 * they are looking for never happened.
 *
 * **Every search runs against what arrived, never against the last search.**
 * Both lists are kept for that reason rather than only a count. A `matching()`
 * that narrowed what was already narrowed would make a backspace leave lines
 * hidden — the person typing widened their term and the window did not widen
 * with it — which is a screen that silently stops being about the thing on it.
 *
 * **The search is over the window, never over the scrollback.** The endpoint
 * takes no search term; it takes a service and a tail. So this narrows what
 * came back, and {@see self::isAWindow()} stays true through the narrowing so a
 * screen can go on saying so.
 *
 * Empty is a legitimate value and means the service has said nothing in the
 * lines that were asked for — told apart from a stack that could not be asked
 * by {@see WhatWasSaid}, not here.
 *
 * @implements IteratorAggregate<int, Said>
 */
final readonly class Scrollback implements IteratorAggregate
{
    /**
     * @param array<int, Said> $arrived what came back, whatever is being shown
     * @param array<int, Said> $lines   what is shown, which a search narrows
     */
    private function __construct(
        private ServiceId $service,
        private HowManyLines $asked,
        private array $arrived,
        private array $lines,
        private LookingFor $looking,
    ) {}

    /**
     * A window as one stack gave it.
     *
     * The service and the bound come before the lines, so a window that could
     * be built from lines alone is unspellable — the argument
     * {@see Stalled::of()} makes about a listing's completeness, and stronger
     * here because both of the facts before the variadic are clauses of the
     * requirement rather than one.
     */
    public static function of(ServiceId $service, HowManyLines $asked, Said ...$lines): self
    {
        // Not habit: a variadic collected from named arguments has string keys,
        // and everything below reads this by position.
        $held = array_values($lines);

        return new self($service, $asked, $held, $held, LookingFor::nothing());
    }

    /**
     * The same window, narrowed to the lines holding what somebody typed.
     *
     * A blank search is not a search — it selects everything, and a screen that
     * treated it as one would put *12 of 200 lines match* over the whole window
     * the moment somebody cleared the box. So it answers the window it was
     * asked of, unnarrowed and saying so.
     *
     * What arrived is carried through untouched, which is the point of this
     * type: the claim about the edge belongs to the read, not to the search.
     */
    public function matching(LookingFor $looking): self
    {
        // Exempt from the mutator that removes this return, because while a
        // blank search selects every line the two are the same program: the
        // loop below asks each line whether it holds the empty string and
        // `mb_stripos` answers 0 to all of them, so it rebuilds this window out
        // of the same lines — and every non-searching `LookingFor` holds the
        // same empty text as `nothing()`, so even the search it reports back is
        // the same value. Nothing can tell the two apart.
        //
        // The exemption removes itself: `ScrollbackTest` asserts both facts it
        // rests on — that a blank search still selects every line, and that a
        // non-searching `LookingFor` still holds nothing — so the day either
        // stops being true that test fails and names this line as the one to
        // delete.
        //
        // Kept rather than deleted because the guard is the statement of intent
        // — a blank search is not a search — and deleting it would leave that
        // resting on what `mb_stripos` does with an empty needle.
        //
        // On the `if` rather than on the return: the annotation is read off the
        // node the traversal enters, and this mutator is reached through the
        // branch.
        // @pest-mutate-ignore: RemoveEarlyReturn
        if (! $looking->isSearching()) {
            return new self($this->service, $this->asked, $this->arrived, $this->arrived, LookingFor::nothing());
        }

        $held = [];

        foreach ($this->arrived as $line) {
            if ($line->holds($looking)) {
                $held[] = $line;
            }
        }

        return new self($this->service, $this->asked, $this->arrived, $held, $looking);
    }

    /** The service this window is over (`N2-R10`). */
    public function service(): ServiceId
    {
        return $this->service;
    }

    /** How many lines were asked for, which is the bound the read was given. */
    public function asked(): HowManyLines
    {
        return $this->asked;
    }

    /**
     * Whether the view stops where it was told to stop (`N2-R10`).
     *
     * True where as many lines came back as were asked for: the bound is the
     * edge of what is shown, and what lies behind it is not carried on the wire
     * and is not guessed at here. False where fewer came back, which says the
     * bound cut nothing and says nothing further — in particular it does not
     * say this is everything the service ever wrote, because the engine keeps
     * what it keeps.
     */
    public function isAWindow(): bool
    {
        return $this->howManyArrived() >= $this->asked->figure();
    }

    /** How many lines came back, before anything narrowed them. */
    public function howManyArrived(): int
    {
        return count($this->arrived);
    }

    /** What somebody is looking for, which may turn out to be nothing. */
    public function lookingFor(): LookingFor
    {
        return $this->looking;
    }

    /** How many are shown, which is fewer than arrived while a search is on. */
    public function count(): int
    {
        return count($this->lines);
    }

    /** @return Traversable<int, Said> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->lines);
    }
}
