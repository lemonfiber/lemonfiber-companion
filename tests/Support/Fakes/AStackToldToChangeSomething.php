<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Adjusting;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheStackMadeOfIt;
use Modules\Kernel\Api\WhatToSet;
use Modules\Kernel\Api\WhereTheChangeStands;
use Override;

/**
 * {@see Adjusting}, answered from what a test put in it.
 *
 * Remembers **which** of the two methods was called, not merely that one was.
 * The port exists to keep a rehearsal and a write apart, so a fake counting
 * them together would let a screen pass that wrote to somebody's stack when it
 * meant to ask what would happen.
 *
 * Named for the asking rather than for the stack, because {@see
 * AStackThatWasAsked} is the doctor's and this is not a second one of those.
 */
final class AStackToldToChangeSomething implements Adjusting
{
    private function __construct(
        private readonly WhereTheChangeStands|Obstacle $answer,
        private ?WhatToSet $askedFor = null,
        private int $rehearsals = 0,
        private int $writes = 0,
    ) {}

    public static function saying(WhereTheChangeStands $stands): self
    {
        return new self($stands);
    }

    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    public function askedFor(): ?WhatToSet
    {
        return $this->askedFor;
    }

    public function rehearsals(): int
    {
        return $this->rehearsals;
    }

    public function writes(): int
    {
        return $this->writes;
    }

    #[Override]
    public function wouldBe(Stack $stack, Session $session, WhatToSet $asked): WhatTheStackMadeOfIt
    {
        $this->askedFor = $asked;
        $this->rehearsals++;

        return $this->answered();
    }

    #[Override]
    public function agreedTo(Stack $stack, Session $session, WhatToSet $asked): WhatTheStackMadeOfIt
    {
        $this->askedFor = $asked;
        $this->writes++;

        return $this->answered();
    }

    private function answered(): WhatTheStackMadeOfIt
    {
        return $this->answer instanceof Obstacle
            ? WhatTheStackMadeOfIt::refused($this->answer)
            : WhatTheStackMadeOfIt::said($this->answer);
    }
}
