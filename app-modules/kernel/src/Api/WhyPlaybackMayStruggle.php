<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Why playback on this machine is likely to struggle whatever app is chosen, or nothing.
 *
 * Said only where the quality preset in force asks for transcoding the
 * platform cannot do in hardware, which is rarely. Every word is empty where
 * the stack said nothing.
 */
final readonly class WhyPlaybackMayStruggle
{
    private function __construct(private string $preset, private string $caution, private string $instead) {}

    /** What the stack said strains playback here; every word is required. */
    public static function said(string $preset, string $caution, string $instead): self
    {
        foreach (['preset' => $preset, 'caution' => $caution, 'instead' => $instead] as $field => $said) {
            if (trim($said) === '') {
                throw AdviceSaysNothing::about($field);
            }
        }

        return new self($preset, $caution, $instead);
    }

    /** Nothing here asks for what this machine cannot give. */
    public static function nothing(): self
    {
        return new self('', '', '');
    }

    /** The preset in force, by the name it was chosen under, or empty. */
    public function preset(): string
    {
        return $this->preset;
    }

    /** What that preset asks of this machine, and what playback does without it, or empty. */
    public function caution(): string
    {
        return $this->caution;
    }

    /** What makes it stop, or empty. */
    public function instead(): string
    {
        return $this->instead;
    }
}
