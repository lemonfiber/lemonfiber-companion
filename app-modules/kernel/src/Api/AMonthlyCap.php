<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * A monthly allowance, what reaching it does, and where the month stands.
 *
 * **Zero is a cap.** An allowance of nothing is a decision somebody made, and
 * it is carried as one; a stack with no cap declared has no `AMonthlyCap` at
 * all, so the two cannot be drawn as each other.
 *
 * Where the month stands is known only where the stack could count it, so it
 * is added by {@see standing()} rather than taken as a nullable argument.
 */
final readonly class AMonthlyCap
{
    private function __construct(
        private int $monthly,
        private WhatACapDoes $does,
        private ?WhereTheMonthStands $standing,
    ) {}

    /** An allowance in bytes, possibly none, and what reaching it does. */
    public static function of(int $monthly, WhatACapDoes $does): self
    {
        if ($monthly < 0) {
            throw LineSaysNothing::negative('monthly', $monthly);
        }

        return new self($monthly, $does, null);
    }

    /** The same cap, with where this month stands against it. */
    public function standing(WhereTheMonthStands $standing): self
    {
        return new self($this->monthly, $this->does, $standing);
    }

    /** The allowance, in bytes. */
    public function monthly(): int
    {
        return $this->monthly;
    }

    /** What happens when it is reached. */
    public function does(): WhatACapDoes
    {
        return $this->does;
    }

    /**
     * Where the month stands against it, or that nothing counted it.
     *
     * @template TStands of object
     * @template TUncounted of object
     *
     * @param Closure(WhereTheMonthStands): TStands $stands
     * @param Closure(): TUncounted                 $uncounted
     *
     * @return TStands|TUncounted
     */
    public function stands(Closure $stands, Closure $uncounted): object
    {
        return $this->standing instanceof WhereTheMonthStands ? $stands($this->standing) : $uncounted();
    }
}
