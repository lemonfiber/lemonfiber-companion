<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The audio format chosen for music, which has no resolution, in the stack's words.
 *
 * Its own type rather than an {@see APresetInForce} with a blank resolution:
 * a format drawn in a resolution's place is music offered as a resolution.
 */
final readonly class AFormatInForce
{
    private function __construct(
        private string $scope,
        private string $format,
        private string $means,
        private string $targets,
        private string $sizePerHour,
        private string $note,
    ) {}

    /** What the stack said of the format in force; every word is required. */
    public static function reported(
        string $scope,
        string $format,
        string $means,
        string $targets,
        string $sizePerHour,
        string $note,
    ): self {
        $words = [
            'scope' => $scope,
            'format' => $format,
            'means' => $means,
            'targets' => $targets,
            'size_per_hour' => $sizePerHour,
            'note' => $note,
        ];

        foreach ($words as $field => $said) {
            if (trim($said) === '') {
                throw QualitySaysNothing::about($field);
            }
        }

        return new self($scope, $format, $means, $targets, $sizePerHour, $note);
    }

    /** What it applies to, as the stack writes it. */
    public function scope(): string
    {
        return $this->scope;
    }

    /** The format's plain-language name. */
    public function format(): string
    {
        return $this->format;
    }

    /** What it means, in the operator's terms. */
    public function means(): string
    {
        return $this->means;
    }

    /** The audio format it aims for, in plain terms. */
    public function targets(): string
    {
        return $this->targets;
    }

    /** Roughly how much room an hour of it takes, as the stack words it. */
    public function sizePerHour(): string
    {
        return $this->sizePerHour;
    }

    /** The caveat worth knowing: playing it, or finding it. */
    public function note(): string
    {
        return $this->note;
    }
}
