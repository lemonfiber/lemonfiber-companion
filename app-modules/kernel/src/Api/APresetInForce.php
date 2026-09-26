<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One quality preset in force, for everything or for one kind of media, in the stack's words.
 *
 * Whether it transcodes is a property of this machine rather than of the
 * preset, which is why it is asked as {@see self::transcodesHere()} and never
 * read off the preset's name.
 */
final readonly class APresetInForce
{
    private function __construct(
        private string $scope,
        private string $preset,
        private string $means,
        private string $resolution,
        private string $sizePerHour,
        private string $transcoding,
        private bool $transcodesHere,
    ) {}

    /** What the stack said of one choice in force; every word is required. */
    public static function reported(
        string $scope,
        string $preset,
        string $means,
        string $resolution,
        string $sizePerHour,
        string $transcoding,
        bool $transcodesHere,
    ): self {
        $words = [
            'scope' => $scope,
            'preset' => $preset,
            'means' => $means,
            'resolution' => $resolution,
            'size_per_hour' => $sizePerHour,
            'transcoding' => $transcoding,
        ];

        foreach ($words as $field => $said) {
            if (trim($said) === '') {
                throw QualitySaysNothing::about($field);
            }
        }

        return new self($scope, $preset, $means, $resolution, $sizePerHour, $transcoding, $transcodesHere);
    }

    /** What it applies to: everything, or one kind of media, as the stack writes it. */
    public function scope(): string
    {
        return $this->scope;
    }

    /** The preset's plain-language name. */
    public function preset(): string
    {
        return $this->preset;
    }

    /** What it means, in the operator's terms. */
    public function means(): string
    {
        return $this->means;
    }

    /** The resolution and encode it aims for. */
    public function resolution(): string
    {
        return $this->resolution;
    }

    /** Roughly how much room an hour of it takes, as the stack words it. */
    public function sizePerHour(): string
    {
        return $this->sizePerHour;
    }

    /** What playing it costs, in plain terms. */
    public function transcoding(): string
    {
        return $this->transcoding;
    }

    /** Whether this machine would have to transcode it in software. */
    public function transcodesHere(): bool
    {
        return $this->transcodesHere;
    }
}
