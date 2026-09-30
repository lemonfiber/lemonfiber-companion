<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Authenticated;
use Modules\Kernel\Api\DeviceAuth;
use Modules\Kernel\Api\Lock;
use Modules\Kernel\Api\WhenTheLockAsks;

/**
 * A device that authenticates, or does not, on a machine that has neither.
 *
 * What every test needing the device's lock hands its subject. The contract
 * test holds it to the same assertions as {@see \Modules\Device\Api\PlatformAuth}
 * over a scripted bridge, so a fake easier to satisfy than the platform fails
 * there rather than quietly making the suite green.
 *
 * It answers as `LockRule` does: the lock stands from a cold start, only the
 * prompt's success or a waiver opens it, and a device with no screen lock never
 * stands. A prompt raised by itself is answered later, by {@see self::answers()},
 * as the device's is.
 */
final class ADeviceThatKnowsYou implements DeviceAuth
{
    private int $asked = 0;

    private int $askedByItself = 0;

    private int $drawn = 0;

    private bool $open;

    private function __construct(
        private readonly bool $available,
        private readonly bool $succeeds,
    ) {
        $this->open = ! $available;
    }

    /** A device with a screen lock, whose operator authenticates. */
    public static function willing(): self
    {
        return new self(available: true, succeeds: true);
    }

    /** A device with a screen lock, whose operator does not. */
    public static function refusing(): self
    {
        return new self(available: true, succeeds: false);
    }

    /** A device with no screen lock configured, which can ask nobody. */
    public static function withNoScreenLock(): self
    {
        return new self(available: false, succeeds: false);
    }

    /** A device whose operator already unlocked it this launch. */
    public static function unlocked(): self
    {
        $device = self::willing();
        $device->open = true;

        return $device;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function unlock(): Lock
    {
        $this->asked++;
        $this->open = $this->open || $this->succeeds;

        return $this->standing();
    }

    public function standing(): Lock
    {
        return $this->open ? Lock::openedBy(Authenticated::byTheDevice()) : Lock::held();
    }

    public function waive(): Lock
    {
        $this->open = true;

        return $this->standing();
    }

    public function drawn(WhenTheLockAsks $asks): Lock
    {
        $this->drawn++;

        if ($asks->byItself() && ! $this->open) {
            $this->askedByItself++;
        }

        return $this->standing();
    }

    /** The prompt the device raised by itself is answered. */
    public function answers(): void
    {
        $this->open = $this->open || ($this->succeeds && $this->askedByItself > 0);
    }

    /** The app came back after longer than it may be away. */
    public function standsAgain(): void
    {
        $this->open = ! $this->available;
    }

    /** How many times the operator asked to be let in. */
    public function asked(): int
    {
        return $this->asked;
    }

    /** How many times the device asked without being asked. */
    public function askedByItself(): int
    {
        return $this->askedByItself;
    }

    /** How many times the lock screen said it was on the glass. */
    public function drawnTimes(): int
    {
        return $this->drawn;
    }
}
