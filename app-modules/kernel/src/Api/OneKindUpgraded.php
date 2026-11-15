<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One kind of media an upgrade covers: the preset in force for it, what upgrading it takes in total, and what asking its service came to.
 *
 * Per kind rather than one figure for the library, because each kind carries
 * its own preset and so its own cost.
 */
final readonly class OneKindUpgraded
{
    private function __construct(
        private string $kind,
        private string $preset,
        private WhatUpgradingItTakes $total,
        private WhatBecameOfAskingIt $asking,
    ) {}

    /** What the stack said of one kind; the kind and the preset are required. */
    public static function reported(string $kind, string $preset, WhatUpgradingItTakes $total, WhatBecameOfAskingIt $asking): self
    {
        foreach (['media_type' => $kind, 'preset' => $preset] as $field => $said) {
            if (trim($said) === '') {
                throw QualitySaysNothing::about($field);
            }
        }

        return new self($kind, $preset, $total, $asking);
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

    /** What upgrading it takes in total, or why the stack could not say. */
    public function total(): WhatUpgradingItTakes
    {
        return $this->total;
    }

    /** What became of asking its service to search again, or that nothing was asked. */
    public function asking(): WhatBecameOfAskingIt
    {
        return $this->asking;
    }
}
