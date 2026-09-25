<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** What asking a stack to rehearse a start came away with: the rehearsal, or what stood in the way. */
final readonly class WhatTheRehearsalFound
{
    private function __construct(private WhatStartingItWouldComeTo|Obstacle $answer) {}

    /** The stack answered, and this is what starting would come to. */
    public static function found(WhatStartingItWouldComeTo $rehearsal): self
    {
        return new self($rehearsal);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template T of object
     *
     * @param Closure(WhatStartingItWouldComeTo): T $found
     * @param Closure(Obstacle): T                  $met
     *
     * @return T
     */
    public function either(Closure $found, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $found($this->answer);
    }
}
