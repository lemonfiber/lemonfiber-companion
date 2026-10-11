<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Artwork held on the device in memory only, for as long as the app runs.
 *
 * Never written to a disk cache, the platform's URL cache, a temporary file or
 * a backup, so nothing of it is ever kept. A stack's pictures are let go of
 * when its session ends or is replaced and when it is removed, and every
 * picture when the lock engages, which the device does on its own.
 */
interface HoldingArtwork
{
    /** Hold a picture under its key for the stack it came from, answering whether it is held. */
    public function hold(ArtworkKey $key, StackId $stack, Artwork $artwork): bool;

    /** Whether a picture is still held under its key. */
    public function holds(ArtworkKey $key): bool;

    /** Let go of every picture held for a stack, answering whether the device did. */
    public function letGoOf(StackId $stack): bool;
}
