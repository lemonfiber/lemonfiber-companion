<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

use function trim;

/**
 * Everything the stack changed, newest first, and how far back that goes.
 *
 * **The horizon travels with the changes and is never optional**: the end
 * of what is kept must not be presentable as nothing having happened. A record holding forty changes means one thing if the stack keeps
 * forty and another if it keeps ninety days, and the entries cannot say which.
 *
 * **An empty record is an answer.** A stack that has changed nothing is told
 * apart from one that could not be asked by never arriving here at all — the
 * second is an {@see Obstacle} on {@see WhatWasRecorded} — so the difference
 * is held by the type rather than by a screen remembering to check.
 *
 * **The order is the stack's and is kept.** Two changes made at the same
 * instant arrive in some order, and this holds that order rather than
 * re-deciding it; what it must not become is *one came before the other*, and
 * that is for the screen to draw as one moment rather than for this to erase.
 *
 * @implements IteratorAggregate<int, Change>
 */
final readonly class TheRecord implements Countable, IteratorAggregate
{
    /** @param list<Change> $changes */
    private function __construct(private string $horizon, private array $changes) {}

    /**
     * The record as the stack gave it.
     *
     * Reindexed for {@see WhatIsUnsupported::these()}'s reason: a variadic
     * collected from named arguments carries their names as keys, so being
     * variadic is not the same claim as being a list, and everything reading
     * this reads it by position.
     */
    public static function reaching(string $horizon, Change ...$changes): self
    {
        $said = trim($horizon);

        if ($said === '') {
            throw TheRecordHasNoHorizon::said();
        }

        return new self($said, array_values($changes));
    }

    /** How far back this goes, in the operator's terms. */
    public function horizon(): string
    {
        return $this->horizon;
    }

    public function count(): int
    {
        return count($this->changes);
    }

    /** @return Traversable<int, Change> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->changes);
    }
}
