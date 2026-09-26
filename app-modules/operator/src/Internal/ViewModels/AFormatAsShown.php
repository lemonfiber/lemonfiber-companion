<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * The format chosen for music, flattened for a template.
 *
 * No resolution, because music has none: what is drawn is what the stack
 * says of the format, as it came. Every field is empty where no format is set.
 */
final readonly class AFormatAsShown
{
    /**
     * @param string $scope       what it applies to, as the stack writes it
     * @param string $format      the format's plain-language name, or empty where none is set
     * @param string $means       what it means, in the operator's terms
     * @param string $targets     the audio format it aims for
     * @param string $sizePerHour roughly how much room an hour of it takes
     * @param string $note        the caveat worth knowing about it
     */
    public function __construct(
        public string $scope,
        public string $format,
        public string $means,
        public string $targets,
        public string $sizePerHour,
        public string $note,
    ) {}

    /** No format is set for music. */
    public static function none(): self
    {
        return new self(scope: '', format: '', means: '', targets: '', sizePerHour: '', note: '');
    }
}
