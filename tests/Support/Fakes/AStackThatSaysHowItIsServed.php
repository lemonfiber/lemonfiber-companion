<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\AskingHowItIsServed;
use Modules\Kernel\Api\HowItIsServed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasSaidOfServing;
use Override;

/**
 * A stack asked how it serves its web surface, answering one thing and counting how often it was asked.
 */
final class AStackThatSaysHowItIsServed implements AskingHowItIsServed
{
    private int $askings = 0;

    /** @param Closure(): WhatWasSaidOfServing $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack served as this. */
    public static function with(HowItIsServed $served): self
    {
        return new self(static fn(): WhatWasSaidOfServing => WhatWasSaidOfServing::said($served));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatWasSaidOfServing => WhatWasSaidOfServing::met($why));
    }

    /** How many times it was asked. */
    public function askings(): int
    {
        return $this->askings;
    }

    #[Override]
    public function served(Stack $stack, Session $session): WhatWasSaidOfServing
    {
        $this->askings++;

        return ($this->answer)();
    }
}
