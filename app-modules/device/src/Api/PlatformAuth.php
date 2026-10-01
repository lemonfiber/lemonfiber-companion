<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Lemonfiber\Native\Screen as Native;
use Modules\Device\Internal\Words;
use Modules\Kernel\Api\Authenticated;
use Modules\Kernel\Api\DeviceAuth;
use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\Lock;
use Modules\Kernel\Api\WhenTheLockAsks;

/**
 * The device's own authentication, reached through lemonfiber's native expansion.
 *
 * Thin on purpose. The lock itself — when it stands, when it may ask, and that
 * only the prompt's success opens it — lives in `LockRule`, in Kotlin and in
 * Swift, with the same cases each. What is decided here is only how an answer
 * becomes a {@see Lock}.
 *
 * **Every answer is read through the bridge, never from an event.** The device
 * wakes the app when the lock moves with an event that carries nothing, and the
 * app then asks here; a lock opened by something an event said would be a lock
 * anything able to send one could open.
 */
final readonly class PlatformAuth implements DeviceAuth
{
    public function __construct(private Native $device, private Words $words) {}

    public function isAvailable(): bool
    {
        return $this->device->canAuthenticate();
    }

    public function unlock(): Lock
    {
        return $this->lock(open: $this->device->authenticate($this->reason()));
    }

    public function standing(): Lock
    {
        return $this->lock(open: $this->device->lockIsOpen());
    }

    public function waive(): Lock
    {
        return $this->lock(open: $this->device->waiveTheLock());
    }

    public function drawn(WhenTheLockAsks $asks): Lock
    {
        return $this->lock(open: $this->device->lockIsDrawn($this->reason(), mayAsk: $asks->byItself()));
    }

    public function allowAway(HowLong $howLong): Lock
    {
        return $this->lock(open: $this->device->lockAfter($howLong->inSeconds()));
    }

    /**
     * The device's answer as a lock.
     *
     * `Authenticated::byTheDevice()` is reached here and from nowhere else in
     * this application: the one line that turns the device saying *open* into
     * an open lock, in one file that exists to be read carefully.
     */
    private function lock(bool $open): Lock
    {
        return $open ? Lock::openedBy(Authenticated::byTheDevice()) : Lock::held();
    }

    /**
     * The sentence the platform shows in its own dialog.
     *
     * From the catalogue rather than from a caller: with no parameter to pass,
     * there is nowhere for a literal to get in.
     */
    private function reason(): string
    {
        return $this->words->for('device.unlock_reason');
    }
}
