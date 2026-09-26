<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One quality preset in force, flattened for a template.
 *
 * Every word is the stack's, as it came.
 */
final readonly class APresetAsShown
{
    /**
     * @param string $scope          what it applies to, as the stack writes it
     * @param string $preset         the preset's plain-language name
     * @param string $means          what it means, in the operator's terms
     * @param string $resolution     the resolution and encode it aims for
     * @param string $sizePerHour    roughly how much room an hour of it takes
     * @param string $transcoding    what playing it costs
     * @param bool   $transcodesHere whether this machine would have to transcode it in software
     */
    public function __construct(
        public string $scope,
        public string $preset,
        public string $means,
        public string $resolution,
        public string $sizePerHour,
        public string $transcoding,
        public bool $transcodesHere,
    ) {}
}
