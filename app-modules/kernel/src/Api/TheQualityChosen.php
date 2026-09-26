<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The quality in force on one stack, and what the stack did with the choice it was last asked about.
 *
 * The same answer whether the stack was only read or asked to choose: a
 * choice comes back as the whole of what is in force, with what became of it.
 */
final readonly class TheQualityChosen
{
    private function __construct(
        private ThePresetsInForce $presets,
        private WhatMusicIsSetTo $music,
        private WhatBecameOfTheChoice $became,
        private bool $customised,
    ) {}

    /** What the stack reported. */
    public static function reported(
        ThePresetsInForce $presets,
        WhatMusicIsSetTo $music,
        WhatBecameOfTheChoice $became,
        bool $customised,
    ): self {
        return new self($presets, $music, $became, $customised);
    }

    /** The presets in force, the overall choice first. */
    public function presets(): ThePresetsInForce
    {
        return $this->presets;
    }

    /** The format chosen for music, or none. */
    public function music(): WhatMusicIsSetTo
    {
        return $this->music;
    }

    /** What became of the choice. */
    public function became(): WhatBecameOfTheChoice
    {
        return $this->became;
    }

    /**
     * Whether the quality configuration was edited by hand since the stack wrote it.
     *
     * Where it was, the preset is no longer in charge of it, and the stack
     * leaves the edit as it stands.
     */
    public function customised(): bool
    {
        return $this->customised;
    }
}
