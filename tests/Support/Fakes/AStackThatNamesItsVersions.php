<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ReadingVersions;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatRunsHere;
use Modules\Kernel\Api\WhatWasFoundOfTheVersions;

/**
 * A stack where a test says which versions it runs, and which remembers being asked.
 *
 * {@see AStackThatChecksItself}' sibling one endpoint along.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatNamesItsVersions implements ReadingVersions
{
    /** How many times it was asked, which is how a screen asking twice a frame is caught. */
    private int $askings = 0;

    /** @param Closure(): WhatWasFoundOfTheVersions $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack running these versions. */
    public static function with(WhatRunsHere $runs): self
    {
        return new self(static fn(): WhatWasFoundOfTheVersions => WhatWasFoundOfTheVersions::found($runs));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatWasFoundOfTheVersions => WhatWasFoundOfTheVersions::met($why));
    }

    /** How many times it was asked. */
    public function askings(): int
    {
        return $this->askings;
    }

    public function versionsOn(Stack $stack, Session $session): WhatWasFoundOfTheVersions
    {
        $this->askings++;

        return ($this->answer)();
    }
}
