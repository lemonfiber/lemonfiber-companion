<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One kind of media an upgrade covers: the preset in force for it, what an hour of that costs, and what asking its service came to.
 *
 * Per kind rather than one figure for the library, because each kind carries
 * its own preset and so its own cost.
 */
final readonly class OneKindUpgraded
{
    private function __construct(
        private string $kind,
        private string $preset,
        private string $sizePerHour,
        private WhatBecameOfAskingIt $asking,
    ) {}

    /** What the stack said of one kind; the kind, the preset and the cost are required. */
    public static function reported(string $kind, string $preset, string $sizePerHour, WhatBecameOfAskingIt $asking): self
    {
        foreach (['media_type' => $kind, 'preset' => $preset, 'size_per_hour' => $sizePerHour] as $field => $said) {
            if (trim($said) === '') {
                throw QualitySaysNothing::about($field);
            }
        }

        return new self($kind, $preset, $sizePerHour, $asking);
    }

    /** The kind of media, as the stack writes it. */
    public function kind(): string
    {
        return $this->kind;
    }

    /** The preset in force for it, which is the bar it would be upgraded to. */
    public function preset(): string
    {
        return $this->preset;
    }

    /** Roughly what an hour of it costs at that preset, as the stack words it. */
    public function sizePerHour(): string
    {
        return $this->sizePerHour;
    }

    /** What became of asking its service to search again, or that nothing was asked. */
    public function asking(): WhatBecameOfAskingIt
    {
        return $this->asking;
    }
}
