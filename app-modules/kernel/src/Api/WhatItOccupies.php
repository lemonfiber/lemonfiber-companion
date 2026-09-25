<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a set of files occupies, counted both ways.
 *
 * `physical` is what the volume has lost to them. `logical` is what they
 * would take with nothing shared, which is larger wherever a file is linked
 * into both the downloads and the library. The two differ only where
 * something is shared.
 */
final readonly class WhatItOccupies
{
    private function __construct(private int $logical, private int $physical) {}

    /** Both figures, in bytes, each refused below nothing. */
    public static function counted(int $logical, int $physical): self
    {
        if ($logical < 0) {
            throw RoomSaysNothing::negative('logical', $logical);
        }

        if ($physical < 0) {
            throw RoomSaysNothing::negative('physical', $physical);
        }

        return new self($logical, $physical);
    }

    /** What the volume has lost to these files. */
    public function physical(): int
    {
        return $this->physical;
    }

    /** What they would take with nothing shared. */
    public function logical(): int
    {
        return $this->logical;
    }

    /** Whether the two figures differ, which is the only case worth showing both. */
    public function differs(): bool
    {
        return $this->logical !== $this->physical;
    }
}
