<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\AskingWhatIsLeftAlone;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAreasLeftAlone;
use Modules\Kernel\Api\WhatWasFoundOfTheAreasLeftAlone;
use Override;

final class AStackThatSaysWhatIsLeftAlone implements AskingWhatIsLeftAlone
{
    private function __construct(
        private readonly WhatWasFoundOfTheAreasLeftAlone $answer,
        private int $askings = 0,
    ) {}

    public static function with(TheAreasLeftAlone $areas): self
    {
        return new self(WhatWasFoundOfTheAreasLeftAlone::listed($areas));
    }

    public static function tooOldToSay(): self
    {
        return new self(WhatWasFoundOfTheAreasLeftAlone::notCarried());
    }

    public static function met(Obstacle $why): self
    {
        return new self(WhatWasFoundOfTheAreasLeftAlone::met($why));
    }

    public function askings(): int
    {
        return $this->askings;
    }

    #[Override]
    public function leftAloneOn(Stack $stack, Session $session): WhatWasFoundOfTheAreasLeftAlone
    {
        $this->askings++;

        return $this->answer;
    }
}
