<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\ForgetsEverythingKept;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WakingWhileAway;

/**
 * Clear saved data: every reading, setting and marker the phone keeps.
 *
 * Asked of every store of what the phone keeps at once, which is the set the
 * composition root registers; a pairing and a session are not among them, so
 * the operator's stacks stay paired and they stay signed in. The lock's time
 * away was one of the settings, so the device is told it is back to its
 * standard. Notifications were another, and are off again, so the platform
 * stops waking the app for them. Every distributor registration and the key
 * pair of each is dropped, and each stack that held this phone's endpoint is
 * owed its removal.
 */
final readonly class ClearingWhatThePhoneKeeps
{
    public function __construct(
        private ForgetsEverythingKept $everything,
        private LockingAfter $locking,
        private WakingWhileAway $waking,
        private WakingThisPhone $woken,
        private Stacks $stacks,
    ) {}

    /** Let go of everything kept, and say how much that was. */
    public function clear(): Forgotten
    {
        $forgotten = $this->woken->clearedAlongside($this->everything, $this->stacks->configured());
        $this->locking->toldTheDevice();
        $this->waking->stop();

        return $forgotten;
    }
}
