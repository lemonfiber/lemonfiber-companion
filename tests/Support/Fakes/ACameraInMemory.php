<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Scanning;
use Modules\Kernel\Api\WhatTheCameraSaw;
use Modules\Kernel\Api\WhyNothingWasScanned;

/**
 * A camera that answers with what a test told it to see.
 *
 * What every test of the scanned road hands its screen. The port's callback
 * shape is what makes a fake necessary rather than convenient: the platform's
 * scanner takes the display and answers later, so there is nothing to return
 * and nothing a suite could wait for.
 *
 * **It answers synchronously, and the contract test is what says that is
 * allowed.** On a handset the callback arrives from the runloop after
 * `forAPairingCode()` has returned; here it arrives inside it. Every assertion
 * either implementation is held to is written in terms of *what the callback
 * was given* rather than *when*, so the difference cannot hide a disagreement
 * — which is the one thing about this fake worth checking, and `G2` is where
 * it is checked.
 *
 * It counts the times it was opened, because "the camera was not opened again"
 * is a claim a screen makes and nothing else could see.
 */
final class ACameraInMemory implements Scanning
{
    private int $opened = 0;

    private function __construct(private readonly WhatTheCameraSaw $sees) {}

    /** A camera that reads the payload named. */
    public static function reading(string $payload): self
    {
        return new self(WhatTheCameraSaw::read($payload));
    }

    /** A camera that comes back with nothing, for the reason named. */
    public static function answering(WhyNothingWasScanned $why): self
    {
        return new self(WhatTheCameraSaw::nothing($why));
    }

    public function forAPairingCode(Closure $saw): void
    {
        $this->opened++;

        $saw($this->sees);
    }

    /** How many times something asked for the camera. */
    public function opened(): int
    {
        return $this->opened;
    }
}
