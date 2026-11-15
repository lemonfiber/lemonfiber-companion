<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\AskingHowItRunsWithoutLemonfiber;
use Modules\Kernel\Api\HowItRunsWithoutLemonfiber;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheCommandLine;
use Modules\Kernel\Api\ThePartsTheWordsLeaveOut;
use Modules\Kernel\Api\WhatWasSaidOfRunningWithout;
use Modules\Kernel\Api\WhoseStackItIs;

/**
 * A stack where a test says how it runs without lemonfiber, and which remembers being asked.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatSaysHowItRunsWithout implements AskingHowItRunsWithoutLemonfiber
{
    /** How many times it was asked, which is how a screen asking twice a frame is caught. */
    private int $askings = 0;

    /** @param Closure(): WhatWasSaidOfRunningWithout $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack that runs like this without lemonfiber. */
    public static function with(HowItRunsWithoutLemonfiber $said): self
    {
        return new self(static fn(): WhatWasSaidOfRunningWithout => WhatWasSaidOfRunningWithout::said($said));
    }

    /** A stack running the bundled stack, brought up by the plainest words. */
    public static function bundled(): self
    {
        return self::with(HowItRunsWithoutLemonfiber::said(WhoseStackItIs::bundled(), '/srv/lemonfiber', '/srv/lemonfiber/.env', TheCommandLine::of('docker', 'compose', 'up', '-d'), 'lemonfiber --yes', ThePartsTheWordsLeaveOut::these()));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatWasSaidOfRunningWithout => WhatWasSaidOfRunningWithout::met($why));
    }

    /** How many times it was asked. */
    public function askings(): int
    {
        return $this->askings;
    }

    public function withoutLemonfiber(Stack $stack, Session $session): WhatWasSaidOfRunningWithout
    {
        $this->askings++;

        return ($this->answer)();
    }
}
