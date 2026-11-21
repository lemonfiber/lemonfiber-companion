<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\AskingWhatSetupDecided;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatSetupDecided;
use Modules\Kernel\Api\WhatWasFoundOfTheDecisions;
use Override;

final class AStackThatSaysWhatSetupDecided implements AskingWhatSetupDecided
{
    private function __construct(
        private readonly WhatWasFoundOfTheDecisions $answer,
        private int $askings = 0,
    ) {}

    public static function with(WhatSetupDecided $decided): self
    {
        return new self(WhatWasFoundOfTheDecisions::found($decided));
    }

    public static function met(Obstacle $why): self
    {
        return new self(WhatWasFoundOfTheDecisions::met($why));
    }

    public function askings(): int
    {
        return $this->askings;
    }

    #[Override]
    public function decidedOn(Stack $stack, Session $session): WhatWasFoundOfTheDecisions
    {
        $this->askings++;

        return $this->answer;
    }
}
