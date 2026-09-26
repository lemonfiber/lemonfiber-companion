<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A preset the operator named, for everything or for one kind of media.
 *
 * Both are the stack's words as the operator wrote them: the stack decides
 * which presets and which kinds of media there are, and refuses a name it
 * does not know. Music is named the same way, with a format for its preset.
 *
 * **No kind is everything.** The wire says so by leaving `media_type` out,
 * and an empty kind here is that absence rather than a kind called nothing.
 */
final readonly class APresetToChoose
{
    private function __construct(private string $preset, private string $kind) {}

    /** This preset for everything, or for the kind named; a blank preset is refused. */
    public static function named(string $preset, string $kind): self
    {
        $named = trim($preset);

        if ($named === '') {
            throw QualitySaysNothing::about('preset');
        }

        return new self($named, trim($kind));
    }

    /** The preset, or the format for music, as it was named. */
    public function preset(): string
    {
        return $this->preset;
    }

    /** The kind of media it is for, or empty where it is for everything. */
    public function kind(): string
    {
        return $this->kind;
    }

    /** Whether it is for everything rather than for one kind of media. */
    public function isForEverything(): bool
    {
        return $this->kind === '';
    }
}
