<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Authenticated;
use Modules\Kernel\Api\DeviceAuth;
use Modules\Kernel\Api\Lock;

/**
 * A device that authenticates, or does not, on a machine that has neither.
 *
 * What every test needing "the operator unlocked the app" hands its subject. The
 * contract test keeps it honest: it is held to the same assertions as
 * {@see \Modules\Device\Api\PlatformAuth}, so a fake easier to satisfy than the
 * platform fails there rather than quietly making the suite green (`G2`).
 *
 * **Nothing here can be open without having been checked.** There is no state
 * meaning "open, but nobody checked", because {@see Lock} cannot express one:
 * `Authenticated` has a private constructor and one maker. A fake wanting to
 * fall back to unlocked would have to call that maker, which is the same line
 * the adapter calls and is exactly as visible.
 */
final class ADeviceThatKnowsYou implements DeviceAuth
{
    private int $asked = 0;

    private function __construct(
        private readonly bool $available,
        private readonly bool $succeeds,
    ) {}

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

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function unlock(): Lock
    {
        $this->asked++;

        return $this->succeeds
            ? Lock::openedBy(Authenticated::byTheDevice())
            : Lock::held();
    }

    /**
     * How many times the operator was interrupted.
     *
     * Asking once per unlock is about exactly that, so the count is the
     * assertion — and something has to be keeping it.
     */
    public function asked(): int
    {
        return $this->asked;
    }
}
