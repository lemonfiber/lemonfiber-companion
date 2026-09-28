<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a stack's services are for, or the reason that could not be learned.
 *
 * The answer {@see Cataloguing} gives, and a value rather than an exception for
 * {@see WhatWasRecorded}'s reason. **A stack declaring nothing comes back
 * through the first arm**: it and a stack that could not be asked both draw an
 * empty list, and only one of them is an answer.
 */
final readonly class WhatTheCatalogueSaid
{
    private function __construct(private TheCatalogue|Obstacle $answer) {}

    /** The stack answered, and this is its catalogue. */
    public static function catalogue(TheCatalogue $catalogue): self
    {
        return new self($catalogue);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TCatalogue of object
     * @template TMet of object
     *
     * @param Closure(TheCatalogue): TCatalogue $catalogue
     * @param Closure(Obstacle): TMet           $met
     *
     * @return TCatalogue|TMet
     */
    public function either(Closure $catalogue, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $catalogue($this->answer);
    }
}
