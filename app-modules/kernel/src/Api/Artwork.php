<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** One picture's bytes, as the core served them, to be held in memory and drawn. */
final readonly class Artwork
{
    private function __construct(private string $bytes) {}

    public static function of(string $bytes): self
    {
        return $bytes === '' ? throw ArtworkSaysNothing::inItsBytes() : new self($bytes);
    }

    public function bytes(): string
    {
        return $this->bytes;
    }
}
