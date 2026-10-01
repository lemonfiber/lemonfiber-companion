<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Both halves of one reading of what a member asked a stack for.
 *
 * What they are owed and what they have asked for come back on one answer, so
 * they are read once and come back together, each with an outcome of its own: a
 * stack can say one and fail to say the other, and either half can be refused
 * while the other is told. Where the stack could not be read at all, both halves
 * are refused for the same reason.
 */
final readonly class WhatTheirAskingSaid
{
    private function __construct(private WhatTheyAreOwed $owed, private WhatTheyAsked $asked) {}

    /** The stack answered, and each half reads as it reads. */
    public static function of(WhatTheyAreOwed $owed, WhatTheyAsked $asked): self
    {
        return new self($owed, $asked);
    }

    /** The stack could not be read, so neither half could. */
    public static function bothRefused(Obstacle $why): self
    {
        return new self(WhatTheyAreOwed::refused($why), WhatTheyAsked::refused($why));
    }

    /** What they are owed. */
    public function owed(): WhatTheyAreOwed
    {
        return $this->owed;
    }

    /** What they have asked for. */
    public function asked(): WhatTheyAsked
    {
        return $this->asked;
    }
}
