<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What choosing a format for music did, flattened for a template.
 */
final readonly class AFormatChoiceAsShown
{
    /**
     * @param string $becameSaid  the catalogue key for what became of the choice
     * @param string $appliedSaid the catalogue key for what became of asking the music service
     * @param string $detail      the service's own account of why it refused, or empty
     */
    public function __construct(
        public AFormatAsShown $format,
        public string $becameSaid,
        public string $appliedSaid,
        public string $detail,
    ) {}
}
