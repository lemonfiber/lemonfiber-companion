<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Lemonfiber\Native\Artwork as TheArtworkHeld;
use Modules\Kernel\Api\Artwork;
use Modules\Kernel\Api\ArtworkKey;
use Modules\Kernel\Api\HoldingArtwork;
use Modules\Kernel\Api\StackId;

/** Artwork held in the device's memory through the bridge. */
final readonly class PlatformArtwork implements HoldingArtwork
{
    public function __construct(private TheArtworkHeld $held) {}

    public function hold(ArtworkKey $key, StackId $stack, Artwork $artwork): bool
    {
        return $this->held->hold($key->said(), $stack->stored(), $artwork->bytes());
    }

    public function holds(ArtworkKey $key): bool
    {
        return $this->held->holds($key->said());
    }

    public function letGoOf(StackId $stack): bool
    {
        return $this->held->letGoOf($stack->stored());
    }
}
