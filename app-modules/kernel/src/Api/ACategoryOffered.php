<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One category the stack offers back as costing nothing: what it is, what it occupies and how many files it holds.
 */
final readonly class ACategoryOffered
{
    /** @param int<0, max> $files */
    private function __construct(
        private WhatALineIsAbout $about,
        private WhatItOccupies $occupies,
        private int $files,
    ) {}

    /** A category on offer, its file count refused below nothing. */
    public static function holding(WhatALineIsAbout $about, WhatItOccupies $occupies, int $files): self
    {
        if ($files < 0) {
            throw RoomSaysNothing::negative('files', $files);
        }

        return new self($about, $occupies, $files);
    }

    /** What the category is. */
    public function about(): WhatALineIsAbout
    {
        return $this->about;
    }

    /** What it occupies. */
    public function occupies(): WhatItOccupies
    {
        return $this->occupies;
    }

    /**
     * How many files it holds.
     *
     * @return int<0, max>
     */
    public function files(): int
    {
        return $this->files;
    }
}
