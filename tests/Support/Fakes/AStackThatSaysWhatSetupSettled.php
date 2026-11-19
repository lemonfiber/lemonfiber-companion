<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\AskingWhatSetupSettled;
use Modules\Kernel\Api\HowFarSetupGot;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheDataRoot;
use Modules\Kernel\Api\TheServiceUser;
use Modules\Kernel\Api\TheWaysOfDownloading;
use Modules\Kernel\Api\WhatSetupSettled;
use Modules\Kernel\Api\WhatWasSaidOfTheSetup;
use Modules\Kernel\Api\WhereTheServiceUserComesFrom;

/**
 * A stack where a test says what setup settled, and which remembers being asked.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatSaysWhatSetupSettled implements AskingWhatSetupSettled
{
    private int $askings = 0;

    /** @param Closure(): WhatWasSaidOfTheSetup $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack set up with its library in /srv/media, both ways of downloading, and its services run as the stack folder's owner. */
    public static function setUp(): self
    {
        return self::with(WhatSetupSettled::said(
            HowFarSetupGot::Done,
            TheDataRoot::at('/srv/media'),
            TheWaysOfDownloading::of(usenet: true, torrent: true),
            TheServiceUser::runsAs(1000, 1000, WhereTheServiceUserComesFrom::StackOwner),
        ));
    }

    /** A stack whose setup settled this. */
    public static function with(WhatSetupSettled $said): self
    {
        return new self(static fn(): WhatWasSaidOfTheSetup => WhatWasSaidOfTheSetup::said($said));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatWasSaidOfTheSetup => WhatWasSaidOfTheSetup::met($why));
    }

    /** How many times it was asked. */
    public function askings(): int
    {
        return $this->askings;
    }

    public function settled(Stack $stack, Session $session): WhatWasSaidOfTheSetup
    {
        $this->askings++;

        return ($this->answer)();
    }
}
