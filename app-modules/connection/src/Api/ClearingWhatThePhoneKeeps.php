<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\ForgetsEverythingKept;
use Modules\Kernel\Api\Forgotten;

/**
 * Clear saved data: every reading, setting and marker the phone keeps.
 *
 * Asked of every store of what the phone keeps at once, which is the set the
 * composition root registers; a pairing and a session are not among them, so
 * the operator's stacks stay paired and they stay signed in. The lock's time
 * away was one of the settings, so the device is told it is back to its
 * standard.
 */
final readonly class ClearingWhatThePhoneKeeps
{
    public function __construct(private ForgetsEverythingKept $everything, private LockingAfter $locking) {}

    /** Let go of everything kept, and say how much that was. */
    public function clear(): Forgotten
    {
        $forgotten = $this->everything->forgetEverything();
        $this->locking->toldTheDevice();

        return $forgotten;
    }
}
