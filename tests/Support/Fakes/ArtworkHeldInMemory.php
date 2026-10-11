<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function count;

use Modules\Kernel\Api\Artwork;
use Modules\Kernel\Api\ArtworkKey;
use Modules\Kernel\Api\HoldingArtwork;
use Modules\Kernel\Api\StackId;

/**
 * The device's memory of artwork, as a list a test can read, and one that can refuse to hold anything.
 */
final class ArtworkHeldInMemory implements HoldingArtwork
{
    /** @var array<string, string> each picture's stack, by its key */
    private array $held = [];

    private function __construct(private readonly bool $holding) {}

    /** A device that holds what it is handed. */
    public static function working(): self
    {
        return new self(holding: true);
    }

    /** A device that holds nothing it is handed. */
    public static function refusing(): self
    {
        return new self(holding: false);
    }

    public function hold(ArtworkKey $key, StackId $stack, Artwork $artwork): bool
    {
        if ($this->holding) {
            $this->held[$key->said()] = $stack->stored();
        }

        return $this->holding;
    }

    public function holds(ArtworkKey $key): bool
    {
        return array_key_exists($key->said(), $this->held);
    }

    public function letGoOf(StackId $stack): bool
    {
        foreach ($this->held as $key => $whose) {
            if ($whose === $stack->stored()) {
                unset($this->held[$key]);
            }
        }

        return true;
    }

    /** How many pictures are held. */
    public function howMany(): int
    {
        return count($this->held);
    }
}
