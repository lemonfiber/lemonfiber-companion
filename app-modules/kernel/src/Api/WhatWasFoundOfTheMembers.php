<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Everybody the media server holds an account for, or the reason they could not be read.
 *
 * The answer {@see Inviting::whoIsIn()} gives, and a value rather than an
 * exception for {@see WhatWasRecorded}'s reason. A household the stack could
 * not read comes back through the second arm, never as nobody.
 */
final readonly class WhatWasFoundOfTheMembers
{
    private function __construct(private TheMembers|Obstacle $answer) {}

    /** The stack answered, and these are its members. */
    public static function found(TheMembers $members): self
    {
        return new self($members);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TFound of object
     * @template TMet of object
     *
     * @param Closure(TheMembers): TFound $found
     * @param Closure(Obstacle): TMet     $met
     *
     * @return TFound|TMet
     */
    public function either(Closure $found, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $found($this->answer);
    }
}
