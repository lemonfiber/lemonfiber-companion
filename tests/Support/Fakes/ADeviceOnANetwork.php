<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Networking;

/**
 * A device with or without a network, as a test says.
 *
 * `B1` — the port exists so that *this phone is in flight mode* is a sentence a
 * test writes rather than a platform state it has to arrange. There is no
 * network to take away from a PHP process on a laptop, and without this the
 * launch's most consequential branch could not be driven at all.
 *
 * It counts how often it was asked, because one of the requirements here is
 * about *order* rather than about an answer: a launch that reached for the
 * network before the device had let the operator in would satisfy every
 * assertion about what it said and still be wrong.
 */
final class ADeviceOnANetwork implements Networking
{
    private int $asked = 0;

    private function __construct(private readonly bool $connected) {}

    /** A device that can reach a network, which says nothing about a stack. */
    public static function connected(): self
    {
        return new self(connected: true);
    }

    /** A device with no network at all — flight mode, or no wifi and no data. */
    public static function withNothingToReachOver(): self
    {
        return new self(connected: false);
    }

    public function isConnected(): bool
    {
        $this->asked++;

        return $this->connected;
    }

    /** How many times something asked, for pinning the order of a launch. */
    public function timesAsked(): int
    {
        return $this->asked;
    }
}
