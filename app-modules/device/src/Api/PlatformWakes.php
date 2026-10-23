<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Lemonfiber\Native\Wakes;
use Modules\Kernel\Api\WakingWhileAway;

/**
 * Being woken while away, through lemonfiber's bridge.
 *
 * How often is the platform's: Android's periodic work at its least interval,
 * iOS's background refresh whenever it chooses.
 */
final readonly class PlatformWakes implements WakingWhileAway
{
    public function __construct(private Wakes $wakes) {}

    public function ask(): bool
    {
        return $this->wakes->ask();
    }

    public function stop(): bool
    {
        return $this->wakes->stop();
    }
}
