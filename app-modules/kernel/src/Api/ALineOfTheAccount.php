<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One line of where the room went: what it is about, what it occupies, and what getting it back would cost.
 *
 * A tree's line carries the tree's name; no other line has one, and there is
 * no way to build a tree without it or anything else with it.
 */
final readonly class ALineOfTheAccount
{
    private function __construct(
        private WhatALineIsAbout $about,
        private string $tree,
        private WhatItOccupies $occupies,
        private WhatGettingItBackCosts $costs,
    ) {}

    /** The line for one directory beneath the data location. */
    public static function forTheTree(string $named, WhatItOccupies $occupies, WhatGettingItBackCosts $costs): self
    {
        if (trim($named) === '') {
            throw RoomSaysNothing::about('name');
        }

        return new self(WhatALineIsAbout::Tree, $named, $occupies, $costs);
    }

    /** The line for anything but a tree. */
    public static function for(WhatALineIsAbout $about, WhatItOccupies $occupies, WhatGettingItBackCosts $costs): self
    {
        if ($about === WhatALineIsAbout::Tree) {
            throw RoomSaysNothing::about('name');
        }

        return new self($about, '', $occupies, $costs);
    }

    /** What the line is about. */
    public function about(): WhatALineIsAbout
    {
        return $this->about;
    }

    /** The tree's name where the line is about one, and empty otherwise. */
    public function tree(): string
    {
        return $this->tree;
    }

    /** What it occupies. */
    public function occupies(): WhatItOccupies
    {
        return $this->occupies;
    }

    /** What getting its room back would cost. */
    public function costs(): WhatGettingItBackCosts
    {
        return $this->costs;
    }
}
