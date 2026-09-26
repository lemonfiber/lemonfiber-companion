<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function is_string;
use function trim;

/**
 * What came of asking a machine to keep a command running, or to stop.
 *
 * Three arms, because an operator is owed a different sentence for each. The
 * stack did it and said what it did; the stack answered and would not, in its
 * own words — a guard asked to guard no forms, a manager that refused; or the
 * request never got an answer, which is an {@see Obstacle} like everywhere else.
 * Folding the second into the third would tell an operator to check their
 * network about a machine that answered and said why.
 *
 * None of them is pending, for {@see Attempted}'s reason: an act this app
 * could not deliver is not held and sent later.
 */
final readonly class HowTheHandoverWent
{
    private function __construct(private WhatTheHandoverDid|string|Obstacle $answer) {}

    /** The stack did it, and this is what it did. */
    public static function did(WhatTheHandoverDid $did): self
    {
        return new self($did);
    }

    /** The stack answered and would not do it, and this is what it said. */
    public static function refused(string $said): self
    {
        $words = trim($said);

        if ($words === '') {
            throw HandoverSaysNothing::where('why it was refused');
        }

        return new self($words);
    }

    /** It never got an answer, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TDid of object
     * @template TRefused of object
     * @template TMet of object
     *
     * @param  Closure(WhatTheHandoverDid): TDid  $did
     * @param  Closure(string): TRefused  $refused
     * @param  Closure(Obstacle): TMet  $met
     * @return TDid|TRefused|TMet
     */
    public function either(Closure $did, Closure $refused, Closure $met): object
    {
        if ($this->answer instanceof Obstacle) {
            return $met($this->answer);
        }

        return is_string($this->answer) ? $refused($this->answer) : $did($this->answer);
    }
}
