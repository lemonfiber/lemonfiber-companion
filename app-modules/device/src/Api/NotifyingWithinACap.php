<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Modules\Device\Internal\WhatWasToldLately;
use Modules\Kernel\Api\Asked;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Notification;
use Modules\Kernel\Api\Notifier;
use Modules\Kernel\Api\SecondsIn;
use Modules\Kernel\Api\Shown;

/**
 * Every notification, but no more than a few a minute from any one stack.
 *
 * A stack that raises more within a minute than {@see self::AT_MOST} is shown
 * as one notification of having more to tell, in place of each that follows:
 * filed once per stack, so it replaces itself rather than stacking, and
 * leading to that stack's health. One stack's flood counts against that
 * stack alone, and never touches another's notifications.
 */
final readonly class NotifyingWithinACap implements Notifier
{
    /** How many notifications one stack may raise within a minute before the rest are told as one. */
    public const int AT_MOST = 3;

    public function __construct(private Notifier $platform, private Clock $clock, private WhatWasToldLately $lately) {}

    public function standing(): Asked
    {
        return $this->platform->standing();
    }

    public function ask(): Asked
    {
        return $this->platform->ask();
    }

    public function show(Notification $notification): Shown
    {
        $now = $this->clock->now();
        $stack = $notification->beyondTheCap()->filedAs();

        if ($this->lately->since($stack, Instant::atEpochSeconds($now->epochSeconds() - SecondsIn::AMinute->value)) >= self::AT_MOST) {
            return $this->platform->show($notification->beyondTheCap());
        }

        $this->lately->told($stack, $now);

        return $this->platform->show($notification);
    }
}
